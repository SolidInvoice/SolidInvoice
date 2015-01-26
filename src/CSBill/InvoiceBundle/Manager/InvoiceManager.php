<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
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
     * @param ManagerRegistry          $doctrine
     * @param EventDispatcherInterface $dispatcher
     * @param FactoryInterface         $stateMachine
     */
    public function __construct(ManagerRegistry $doctrine, EventDispatcherInterface $dispatcher, FactoryInterface $stateMachine)
    {
        $this->entityManager = $doctrine->getManager();
        $this->dispatcher = $dispatcher;
        $this->stateMachine = $stateMachine;
    }

    /**
     * Create an invoice from a quote
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
        $this->accept($invoice); // ?? Do we really want to accept it immediately after creating it? I think not...

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
     * @throws InvalidTransitionException
     */
    public function create(Invoice $invoice)
    {
        $this->applyTransition($invoice, Graph::TRANSITION_NEW);

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_CREATE, new InvoiceEvent($invoice));

        $this->entityManager->persist($invoice);
        $this->entityManager->flush($invoice);

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_CREATE, new InvoiceEvent($invoice));

        return $invoice;
    }

    /**
     * Accepts an invoice
     *
     * @param Invoice $invoice
     *
     * @return Invoice
     * @throws InvalidTransitionException
     */
    public function accept($invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_ACCEPT, new InvoiceEvent($invoice));

        $this->applyTransition($invoice, Graph::TRANSITION_ACCEPT);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush($invoice);

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_ACCEPT, new InvoiceEvent($invoice));

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     *
     * @return Invoice
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
     * @throws InvalidTransitionException
     */
    public function cancel(Invoice $invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_PAID, new InvoiceEvent($invoice));

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
     * @throws InvalidTransitionException
     */
    public function reopen(Invoice $invoice)
    {
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PRE_PAID, new InvoiceEvent($invoice));

        $this->applyTransition($invoice, Graph::TRANSITION_REOPEN);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_POST_CANCEL, new InvoiceEvent($invoice));

        return $invoice;
    }

    /**
     * @param Invoice $invoice
     * @param string  $transition
     *
     * @return bool
     * @throws InvalidTransitionException
     */
    private function applyTransition(Invoice $invoice, $transition)
    {
        $stateMachine = $this->stateMachine->get($invoice, Graph::GRAPH);

        if ($stateMachine->can($transition)) {
            $stateMachine->apply($transition);

            return true;
        }

        throw new InvalidTransitionException($transition);
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
