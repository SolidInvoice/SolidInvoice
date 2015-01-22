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

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Entity\Item;
use CSBill\InvoiceBundle\Entity\Status;
use CSBill\InvoiceBundle\Event\InvoiceEvents;
use CSBill\InvoiceBundle\Event\InvoicePaidEvent;
use CSBill\QuoteBundle\Entity\Quote;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Doctrine\Common\Persistence\ObjectManager;

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
     * @var \CSBill\InvoiceBundle\Repository\InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @param ManagerRegistry          $doctrine
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ManagerRegistry $doctrine, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $doctrine->getManager();
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return \CSBill\InvoiceBundle\Repository\InvoiceRepository
     */
    protected function getInvoiceRepository()
    {
        if (null === $this->invoiceRepository) {
            $this->invoiceRepository = $this->entityManager->getRepository('CSBillInvoiceBundle:Invoice');
        }

        return $this->invoiceRepository;
    }

    /**
     * Create an invoice from a quote
     *
     * @param  Quote $quote
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
        $invoice->setTax($quote->getTax());
        $invoice->setTotal($quote->getTotal());
        $invoice->setTerms($quote->getTerms());
        $invoice->setDue($quote->getDue());
        $invoice->setUsers($quote->getUsers()->toArray());

        /** @var \CSBill\QuoteBundle\Entity\Item $item */
        foreach ($quote->getItems() as $item) {
            $invoiceItem = new Item();
            $invoiceItem->setCreated($now);
            $invoiceItem->setTotal($item->getTotal());
            $invoiceItem->setTax($item->getTax());
            $invoiceItem->setDescription($item->getDescription());
            $invoiceItem->setPrice($item->getPrice());
            $invoiceItem->setQty($item->getQty());

            $invoice->addItem($invoiceItem);
        }

        $status = $this->entityManager->getRepository('CSBillInvoiceBundle:Status')->findOneByName(Status::STATUS_PENDING);
        $invoice->setStatus($status);

        return $invoice;
    }

    /**
     * @param  string $status
     * @param  Client $client
     *
     * @return int
     */
    public function getCount($status = null, Client $client = null)
    {
        return $this->getInvoiceRepository()->getCountByStatus($status, $client);
    }

    /**
     * @param  Client $client
     *
     * @return int
     */
    public function getTotalIncome(Client $client = null)
    {
        return $this->getInvoiceRepository()->getTotalIncome($client);
    }

    /**
     * @param  Client $client
     *
     * @return int
     */
    public function getTotalOutstanding(Client $client = null)
    {
        return $this->getInvoiceRepository()->getTotalOutstanding($client);
    }

    /**
     * @param Invoice $invoice
     */
    public function markPaid(Invoice $invoice)
    {
        $invoice->setPaidDate(new \DateTime('NOW'));

        $status = $this
            ->entityManager
            ->getRepository('CSBillInvoiceBundle:Status')
            ->findOneBy(array('name' => Status::STATUS_PAID));

        $invoice->setStatus($status);

        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        $event = new InvoicePaidEvent();
        $event->setInvoice($invoice);
        $this->dispatcher->dispatch(InvoiceEvents::INVOICE_PAID, $event);
    }
}
