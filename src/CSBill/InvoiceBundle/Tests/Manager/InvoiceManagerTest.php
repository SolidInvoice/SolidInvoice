<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Tests\Manager;

use CSBill\ClientBundle\Entity\Client;
use CSBill\TaxBundle\Entity\Tax;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\QuoteBundle\Entity\Item;
use CSBill\QuoteBundle\Entity\Quote;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvoiceManagerTest extends KernelTestCase
{
    /**
     * @var InvoiceManager
     */
    private $manager;

    /**
     * @var \Mockery\Mock
     */
    private $dispatcher;

    /**
     * @var \Mockery\Mock
     */
    private $entityManager;

    public function setUp()
    {
        $this->dispatcher = \Mockery::mock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->entityManager = \Mockery::mock('Doctrine\ORM\EntityManagerInterface');
        $stateMachine = \Mockery::mock('Finite\Factory\FactoryInterface', array('can' => true));
        $finite = \Mockery::mock('Finite\Factory\FactoryInterface', array('get' => $stateMachine));
        $doctrine = \Mockery::mock('Doctrine\Common\Persistence\ManagerRegistry', array('getManager' => $this->entityManager));
        $notification = \Mockery::mock('CSBill\NotificationBundle\Notification\NotificationManager');

        $notification->shouldReceive('sendNotification')
            ->andReturn(null);

        $this->manager = new InvoiceManager($doctrine, $this->dispatcher, $finite, $notification);

        $this
            ->entityManager
            ->shouldReceive('persist', 'flush')
            ->zeroOrMoreTimes();

        $stateMachine->shouldReceive('apply')->zeroOrMoreTimes();
    }

    public function testCreateFromQuote()
    {
        $this
            ->dispatcher
            ->shouldReceive('dispatch')
            ->withAnyArgs();

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new \DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate('14');
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Item();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new \DateTime('now'));
        $item->setPrice(120);
        $item->setQty(10);

        $quote = new Quote();
        $quote->setBaseTotal(123);
        $quote->setDiscount(12);
        $quote->setNotes('Notes');
        $quote->setTax(432);
        $quote->setTerms('Terms');
        $quote->setTotal(987);
        $quote->setClient($client);
        $quote->addItem($item);

        $invoice = $this->manager->createFromQuote($quote);

        $this->assertSame($quote->getTotal(), $invoice->getTotal());
        $this->assertSame($quote->getBaseTotal(), $invoice->getBaseTotal());
        $this->assertSame($quote->getDiscount(), $invoice->getDiscount());
        $this->assertSame($quote->getNotes(), $invoice->getNotes());
        $this->assertSame($quote->getTerms(), $invoice->getTerms());
        $this->assertSame($quote->getTax(), $invoice->getTax());
        $this->assertSame($client, $invoice->getClient());
        $this->assertSame(null, $invoice->getStatus());

        $this->assertNotSame($quote->getUuid(), $invoice->getUuid());
        $this->assertNull($invoice->getId());

        $this->assertCount(1, $invoice->getItems());

        /** @var \CSBill\InvoiceBundle\Entity\item[] $invoiceItem */
        $invoiceItem = $invoice->getItems();
        $this->assertInstanceOf('CSBill\InvoiceBundle\Entity\item', $invoiceItem[0]);

        $this->assertSame($item->getTax(), $invoiceItem[0]->getTax());
        $this->assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        $this->assertInstanceOf('DateTime', $invoiceItem[0]->getCreated());
        $this->assertSame($item->getPrice(), $invoiceItem[0]->getPrice());
        $this->assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }

    public function testMarkPaid()
    {
        $invoice = new Invoice();

        $this
            ->dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->withAnyArgs();

        $this
            ->dispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->withAnyArgs();

        // Ensure paid date is empty when creating invoice
        $this->assertNull($invoice->getPaidDate());

        $this->manager->pay($invoice);

        $this->assertInstanceOf('DateTime', $invoice->getPaidDate());
        $this->assertSame(null, $invoice->getStatus());
    }
}
