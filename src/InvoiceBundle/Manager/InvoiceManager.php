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
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use JsonException;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Notification\InvoiceStatusNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use function str_replace;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Manager\InvoiceManagerTest
 */
class InvoiceManager
{
    protected ObjectManager $entityManager;

    protected EventDispatcherInterface $dispatcher;

    public function __construct(
        ManagerRegistry $doctrine,
        EventDispatcherInterface $dispatcher,
        private readonly WorkflowInterface $invoiceStateMachine,
        private readonly NotificationManager $notification,
        private readonly BillingIdGenerator $billingIdGenerator,
    ) {
        $this->entityManager = $doctrine->getManager();
        $this->dispatcher = $dispatcher;
    }

    /**
     * @throws MathException
     */
    public function createFromQuote(Quote $quote): Invoice
    {
        return $this->createFromObject($quote);
    }

    /**
     * @throws MathException
     */
    public function createFromRecurring(RecurringInvoice $recurringInvoice): Invoice
    {
        $invoice = $this->createFromObject($recurringInvoice);

        $now = CarbonImmutable::now();

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
     * @throws MathException
     */
    private function createFromObject($object): Invoice
    {
        /** @var RecurringInvoice|Quote $object */
        $invoice = new Invoice();

        $now = Carbon::now();

        $invoice->setCreated($now);
        $invoice->setClient($object->getClient());
        $invoice->setBaseTotal($object->getBaseTotal());
        $invoice->setDiscount($object->getDiscount());
        $invoice->setNotes($object->getNotes());
        $invoice->setTotal($object->getTotal());
        $invoice->setTerms($object->getTerms());
        $invoice->setUsers($object->getUsers()->toArray());
        $invoice->setBalance($invoice->getTotal());
        $invoice->setCompany($object->getCompany());
        $invoice->setInvoiceId($this->billingIdGenerator->generate($invoice, ['field' => 'invoiceId']));

        if (null !== $object->getTax()) {
            $invoice->setTax($object->getTax());
        }

        /** @var \SolidInvoice\QuoteBundle\Entity\Line $item */
        foreach ($object->getLines() as $item) {
            $invoiceItem = new Line();
            $invoiceItem->setCreated($now);
            $invoiceItem->setTotal($item->getTotal());
            $invoiceItem->setDescription($item->getDescription());
            $invoiceItem->setPrice($item->getPrice());
            $invoiceItem->setQty($item->getQty());

            if ($item->getTax() instanceof Tax) {
                $invoiceItem->setTax($item->getTax());
            }

            $invoice->addLine($invoiceItem);
        }

        if ($object instanceof Quote) {
            $invoice->setQuote($object);
        }

        return $invoice;
    }

    /**
     * @throws InvalidTransitionException
     * @throws JsonException
     */
    public function create(BaseInvoice $invoice): BaseInvoice
    {
        // Set the invoice status as new and save, before we transition to the correct status
        $invoice->setStatus(Graph::STATUS_NEW);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->applyTransition($invoice, Graph::TRANSITION_NEW);

        $this->dispatcher->dispatch(new InvoiceEvent($invoice), InvoiceEvents::INVOICE_PRE_CREATE);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(new InvoiceEvent($invoice), InvoiceEvents::INVOICE_POST_CREATE);

        return $invoice;
    }

    /**
     * @throws InvalidTransitionException|JsonException
     */
    private function applyTransition(BaseInvoice $invoice, string $transition): void
    {
        if ($this->invoiceStateMachine->can($invoice, $transition)) {
            // Do not send status updates for new invoices
            if (Graph::TRANSITION_NEW !== $transition) {
                $oldStatus = $invoice->getStatus();

                $this->invoiceStateMachine->apply($invoice, $transition);

                $newStatus = $invoice->getStatus();

                $parameters = [
                    'invoice' => $invoice,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'transition' => $transition,
                ];

                $this->notification->sendNotification(new InvoiceStatusNotification($parameters));
            }

            return;
        }

        throw new InvalidTransitionException($transition);
    }
}
