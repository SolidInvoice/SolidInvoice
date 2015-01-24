<?php

namespace CSBill\InvoiceBundle\Tests\Manager;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Entity\Tax;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Event\InvoiceEvents;
use CSBill\InvoiceBundle\Event\InvoicePaidEvent;
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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $finite;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    public function setUp()
    {
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $this->finite = $this->getMock('Finite\Factory\FactoryInterface');
        $stateMachine = $this->getMock('Finite\Factory\StateMachineInterface', array('can', 'apply'));
        $doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($this->entityManager));

        $this->finite->expects($this->any())
            ->method('get')
            ->will($this->returnValue($stateMachine));

        $stateMachine->expects($this->any())
            ->method('can')
            ->will($this->returnValue(true));

        $this->manager = new InvoiceManager($doctrine, $this->dispatcher, $this->finite);
    }

    public function testCreateFromQuote()
    {
        $this
            ->entityManager
            ->expects($this->any())
            ->method('persist');

        $this
            ->entityManager
            ->expects($this->any())
            ->method('flush');

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
        $quote->setDue(new \DateTime('now'));
        $quote->setClient($client);
        $quote->addItem($item);

        $invoice = $this->manager->createFromQuote($quote);

        $this->assertSame($quote->getTotal(), $invoice->getTotal());
        $this->assertSame($quote->getBaseTotal(), $invoice->getBaseTotal());
        $this->assertSame($quote->getDue(), $invoice->getDue());
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
            ->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($invoice);

        $this
            ->entityManager
            ->expects($this->once())
            ->method('flush');

        $event = new InvoicePaidEvent();
        $event->setInvoice($invoice);

        $this
            ->dispatcher
            ->expects($this->at(0))
            ->method('dispatch')
            ->with(InvoiceEvents::INVOICE_PRE_PAID, $event);

        $this
            ->dispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with(InvoiceEvents::INVOICE_POST_PAID, $event);

        // Ensure paid date is empty when creating invoice
        $this->assertNull($invoice->getPaidDate());

        $this->manager->pay($invoice);

        $this->assertInstanceOf('DateTime', $invoice->getPaidDate());
        $this->assertSame(null, $invoice->getStatus());
    }
}
