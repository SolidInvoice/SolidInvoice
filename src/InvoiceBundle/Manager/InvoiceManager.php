<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Manager;

use Brick\Math\Exception\MathException;
use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Psr\Clock\ClockInterface;
use Psr\Container\ContainerExceptionInterface;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldValueCopier;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Notification\InvoiceStatusNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Service\TaxSnapshotCopier;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Workflow\WorkflowInterface;
use function str_replace;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Manager\InvoiceManagerTest
 */
class InvoiceManager
{
    private readonly ObjectManager $entityManager;

    public function __construct(
        ManagerRegistry $doctrine,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly NotificationManager $notification,
        private readonly BillingIdGenerator $billingIdGenerator,
        private readonly ClockInterface $clock,
        private readonly CustomFieldValueCopier $customFieldValueCopier,
        private readonly TaxSnapshotCopier $taxSnapshotCopier = new TaxSnapshotCopier(),
    ) {
        $this->entityManager = $doctrine->getManager();
    }

    /**
     * @throws MathException|ContainerExceptionInterface
     */
    public function createFromQuote(Quote $quote): Invoice
    {
        return $this->createFromObject($quote, freezeSnapshots: false)
            ->setQuote($quote);
    }

    /**
     * @throws MathException|ContainerExceptionInterface
     */
    public function createFromRecurring(RecurringInvoice $recurringInvoice): Invoice
    {
        $invoice = $this->createFromObject($recurringInvoice, freezeSnapshots: true);
        $invoice->setRecurringInvoice($recurringInvoice);

        $now = CarbonImmutable::instance($this->clock->now());

        /** @var Line $item */
        foreach ($invoice->getLines() as $item) {
            $description = $item->getDescription();

            $description = str_replace(
                [
                    '{day}',
                    '{day_name}',
                    '{month}',
                    '{year}',
                ],
                [
                    $now->day,
                    $now->format('l'),
                    $now->format('F'),
                    $now->year,
                ],
                $description
            );

            $item->setDescription($description);
        }

        return $invoice;
    }

    /**
     * @throws MathException|ContainerExceptionInterface
     */
    private function createFromObject(RecurringInvoice | Quote $object, bool $freezeSnapshots = false): Invoice
    {
        /** @var RecurringInvoice|Quote $object */
        $invoice = new Invoice();

        $now = $this->clock->now();
        $freezeAt = $freezeSnapshots ? $now : null;

        $invoice->setCreated($now);
        $invoice->setInvoiceDate($now);
        $invoice->setClient($object->getClient());
        $invoice->setBaseTotal($object->getBaseTotal());
        $invoice->setDiscount($object->getDiscount());
        $invoice->setNotes($object->getNotes());
        $invoice->setTotal($object->getTotal());
        $invoice->setTerms($object->getTerms());
        $invoice->setBalance($invoice->getTotal());
        $invoice->setCompany($object->getCompany());
        $invoice->setInvoiceId($this->billingIdGenerator->generate($invoice, ['field' => 'invoiceId']));

        foreach ($object->getUsers() as $user) {
            $invoice->addUser($user);
        }

        $invoice->setTax($object->getTax());

        /** @var \SolidInvoice\QuoteBundle\Entity\Line $item */
        foreach ($object->getLines() as $item) {
            $invoiceItem = new Line();
            $invoiceItem->setCreated($now);
            $invoiceItem->setTotal($item->getTotal());
            $invoiceItem->setDescription($item->getDescription());
            $invoiceItem->setPrice($item->getPrice());
            $invoiceItem->setQty($item->getQty());

            // Snapshot fresh LineTax rows so the new invoice owns its own tax history.
            $invoiceItem->getTaxes()->clear();
            foreach ($item->getTaxes() as $sourceLineTax) {
                $invoiceItem->addTax($this->taxSnapshotCopier->copyLineTax($sourceLineTax, $freezeAt));
            }

            $invoice->addLine($invoiceItem);
        }

        foreach ($object->getInvoiceTaxes() as $sourceInvoiceTax) {
            $invoice->addInvoiceTax($this->taxSnapshotCopier->copyInvoiceTax($sourceInvoiceTax, $freezeAt));
        }

        return $invoice;
    }

    /**
     * @throws InvalidTransitionException
     */
    public function create(Invoice $invoice): Invoice
    {
        // Set the invoice status as new and save, before we transition to the correct status
        $invoice->setStatus(InvoiceStatus::New);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->propagateCustomFields($invoice);

        $this->applyTransition($invoice);

        $this->dispatcher->dispatch(new InvoiceEvent($invoice), InvoiceEvents::INVOICE_PRE_CREATE);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(new InvoiceEvent($invoice), InvoiceEvents::INVOICE_POST_CREATE);

        return $invoice;
    }

    private function propagateCustomFields(Invoice $invoice): void
    {
        $invoiceId = $invoice->getId();
        if (! $invoiceId instanceof Ulid) {
            return;
        }

        $quote = $invoice->getQuote();
        if ($quote instanceof Quote && $quote->getId() instanceof Ulid) {
            $this->customFieldValueCopier->copy(
                CustomFieldTarget::QUOTE,
                $quote->getId(),
                CustomFieldTarget::INVOICE,
                $invoiceId,
            );

            return;
        }

        $recurring = $invoice->getRecurringInvoice();
        if ($recurring instanceof RecurringInvoice && $recurring->getId() instanceof Ulid) {
            $this->customFieldValueCopier->copy(
                CustomFieldTarget::INVOICE,
                $recurring->getId(),
                CustomFieldTarget::INVOICE,
                $invoiceId,
            );
        }
    }

    /**
     * @throws InvalidTransitionException
     */
    private function applyTransition(Invoice $invoice): void
    {
        if (! $this->invoiceStateMachine->can($invoice, Graph::TRANSITION_NEW)) {
            throw new InvalidTransitionException(Graph::TRANSITION_NEW);
        }

        $oldStatus = $invoice->getStatus();

        $this->invoiceStateMachine->apply($invoice, Graph::TRANSITION_NEW);

        $newStatus = $invoice->getStatus();

        $parameters = [
            'invoice' => $invoice,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'transition' => Graph::TRANSITION_NEW,
        ];

        $this->notification->sendNotification(new InvoiceStatusNotification($parameters));
    }
}
