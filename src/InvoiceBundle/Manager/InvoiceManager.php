<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Manager;

use Carbon\Carbon;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use SolidInvoice\InvoiceBundle\Exception\InvalidTransitionException;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Notification\InvoiceStatusNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\StateMachine;

class InvoiceManager implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var StateMachine
     */
    private $stateMachine;

    /**
     * @var NotificationManager
     */
    private $notification;

    /**
     * @param ManagerRegistry          $doctrine
     * @param EventDispatcherInterface $dispatcher
     * @param StateMachine             $stateMachine
     * @param NotificationManager      $notification
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EventDispatcherInterface $dispatcher,
        StateMachine $stateMachine,
        NotificationManager $notification
    ) {
        $this->entityManager = $doctrine->getManager();
        $this->dispatcher = $dispatcher;
        $this->stateMachine = $stateMachine;
        $this->notification = $notification;
    }

    /**
     * Create an invoice from a quote.
     *
     * @param Quote $quote
     *
     * @return Invoice
     */
    public function createFromQuote(Quote $quote): Invoice
    {
        $invoice = new Invoice();

        $now = Carbon::now();

        $invoice->setCreated($now);
        $invoice->setClient($quote->getClient());
        $invoice->setBaseTotal($quote->getBaseTotal());
        $invoice->setDiscount($quote->getDiscount());
        $invoice->setNotes($quote->getNotes());
        $invoice->setTotal($quote->getTotal());
        $invoice->setTerms($quote->getTerms());
        $invoice->setUsers($quote->getUsers()->toArray());
        $invoice->setBalance($invoice->getTotal());

        if (null !== $quote->getTax()) {
            $invoice->setTax($quote->getTax());
        }

        /** @var \SolidInvoice\QuoteBundle\Entity\Item $item */
        foreach ($quote->getItems() as $item) {
            $invoiceItem = new Item();
            $invoiceItem->setCreated($now);
            $invoiceItem->setTotal($item->getTotal());
            $invoiceItem->setDescription($item->getDescription());
            $invoiceItem->setPrice($item->getPrice());
            $invoiceItem->setQty($item->getQty());

            if (null !== $item->getTax()) {
                $invoiceItem->setTax($item->getTax());
            }

            $invoice->addItem($invoiceItem);
        }

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     *
     * @throws InvalidTransitionException
     */
    public function create(Invoice $invoice): Invoice
    {
        if ($invoice->isRecurring()) {
            $invoice->setStatus(Graph::STATUS_RECURRING);

            $firstInvoice = clone $invoice;
            $firstInvoice->setRecurring(false);
            $firstInvoice->setRecurringInfo(null);

            $this->entityManager->persist($invoice);
            $this->entityManager->flush();

            $invoice = $firstInvoice;
        }

        // Set the invoice status as new and save, before we transition to the correct status
        $invoice->setStatus(Graph::STATUS_NEW);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->applyTransition($invoice, Graph::TRANSITION_NEW);

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_CREATE, new InvoiceEvent($invoice));

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_CREATE, new InvoiceEvent($invoice));

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     * @param string  $transition
     *
     * @return bool
     *
     * @throws InvalidTransitionException
     */
    private function applyTransition(Invoice $invoice, string $transition): bool
    {
        if ($this->stateMachine->can($invoice, $transition)) {
            $oldStatus = $invoice->getStatus();

            $this->stateMachine->apply($invoice, $transition);

            $newStatus = $invoice->getStatus();

            $parameters = [
                'invoice' => $invoice,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'transition' => $transition,
            ];

            // Do not send status updates for new invoices
            if ($transition !== Graph::TRANSITION_NEW) {
                $notification = new InvoiceStatusNotification($parameters);

                $this->notification->sendNotification('invoice_status_update', $notification);
            }

            return true;
        }

        throw new InvalidTransitionException($transition);
    }
}
