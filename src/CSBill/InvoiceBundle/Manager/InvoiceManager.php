<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Manager;

use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Entity\Item;
use CSBill\InvoiceBundle\Event\InvoiceEvent;
use CSBill\InvoiceBundle\Event\InvoiceEvents;
use CSBill\InvoiceBundle\Event\InvoicePaidEvent;
use CSBill\InvoiceBundle\Exception\InvalidTransitionException;
use CSBill\InvoiceBundle\Model\Graph;
use CSBill\InvoiceBundle\Notification\InvoiceStatusNotification;
use CSBill\NotificationBundle\Notification\NotificationManager;
use CSBill\PaymentBundle\Repository\PaymentRepository;
use CSBill\QuoteBundle\Entity\Quote;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Finite\Factory\FactoryInterface;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class InvoiceManager extends ContainerAware
{
    /**
     * @var ObjectManager
     */
    protected $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var FactoryInterface
     */
    private $stateMachine;

    /**
     * @var NotificationManager
     */
    private $notification;

    /**
     * @param ManagerRegistry          $doctrine
     * @param EventDispatcherInterface $dispatcher
     * @param FactoryInterface         $stateMachine
     * @param NotificationManager      $notification
     */
    public function __construct(
        ManagerRegistry $doctrine,
        EventDispatcherInterface $dispatcher,
        FactoryInterface $stateMachine,
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
    public function createFromQuote(Quote $quote)
    {
        $invoice = new Invoice();

        $now = new \DateTime('NOW');

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

        /** @var \CSBill\QuoteBundle\Entity\Item $item */
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

        $this->create($invoice);
        $this->accept($invoice); // ?? Do we really want to accept it immediately after creating it?

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     *
     * @throws InvalidTransitionException
     */
    public function create(Invoice $invoice)
    {
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
     * Accepts an invoice.
     *
     * @param Invoice $invoice
     *
     * @return Invoice
     *
     * @throws InvalidTransitionException
     */
    public function accept($invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_ACCEPT, new InvoiceEvent($invoice));

        $this->applyTransition($invoice, Graph::TRANSITION_ACCEPT);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_ACCEPT, new InvoiceEvent($invoice));

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     *
     * @throws InvalidTransitionException
     */
    public function pay(Invoice $invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_PAID, new InvoicePaidEvent($invoice));

        $this->applyTransition($invoice, Graph::TRANSITION_PAY);

        $invoice->setPaidDate(new \DateTime('NOW'));

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_PAID, new InvoicePaidEvent($invoice));

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     *
     * @throws InvalidTransitionException
     */
    public function cancel(Invoice $invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_CANCEL, new InvoiceEvent($invoice));

        $this->applyTransition($invoice, Graph::TRANSITION_CANCEL);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_CANCEL, new InvoiceEvent($invoice));

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     *
     * @throws InvalidTransitionException
     */
    public function reopen(Invoice $invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_REOPEN, new InvoiceEvent($invoice));

        $this->applyTransition($invoice, Graph::TRANSITION_REOPEN);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_REOPEN, new InvoiceEvent($invoice));

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     *
     * @throws InvalidTransitionException
     */
    public function archive(Invoice $invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_ARCHIVE, new InvoiceEvent($invoice));

        $this->applyTransition($invoice, Graph::TRANSITION_ARCHIVE);
        $invoice->archive();

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_ARCHIVE, new InvoiceEvent($invoice));

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
    private function applyTransition(Invoice $invoice, $transition)
    {
        $stateMachine = $this->stateMachine->get($invoice, Graph::GRAPH);

        if ($stateMachine->can($transition)) {
            $oldStatus = $invoice->getStatus();

            $stateMachine->apply($transition);

            $newStatus = $invoice->getStatus();

            $parameters = array(
                'invoice' => $invoice,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'transition' => $transition,
            );

            // Do not send status updates for new invoices
            if ($transition !== Graph::TRANSITION_NEW) {
                $notification = new InvoiceStatusNotification($parameters);

                $this->notification->sendNotification('invoice_status_update', $notification);
            }

            return true;
        }

        throw new InvalidTransitionException($transition);
    }

    /**
     * Checks if an invoice has been paid in full.
     *
     * @param Invoice $invoice
     *
     * @return bool
     */
    public function isFullyPaid(Invoice $invoice)
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->entityManager->getRepository('CSBillPaymentBundle:Payment');

        $totalPaid = $paymentRepository->getTotalPaidForInvoice($invoice);

        return $totalPaid >= $invoice->getBalance();
    }

    /**
     * @param string $method
     * @param array  $args
     *
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        throw new InvalidTransitionException($method);
    }
}
