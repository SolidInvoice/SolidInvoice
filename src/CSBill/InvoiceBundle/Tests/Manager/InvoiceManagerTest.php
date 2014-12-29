<?php

namespace CSBill\InvoiceBundle\Tests\Manager;

use CSBill\ClientBundle\Entity\Client;
use CSBill\CoreBundle\Entity\Tax;
use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\InvoiceBundle\Entity\Status;
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
    private $entityManager;

    public function setUp()
    {
        $this->dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $doctrine = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $doctrine->expects($this->once())
            ->method('getManager')
            ->will($this->returnValue($this->entityManager));

        $this->manager = new InvoiceManager($doctrine, $this->dispatcher);
    }

    protected function getInvoiceRepositoryMock()
    {
        $repository = $this->getMockBuilder('CSBill\InvoiceBundle\Repository\InvoiceRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with('CSBillInvoiceBundle:Invoice')
            ->will($this->returnValue($repository));

        return $repository;
    }

    public function testCreateFromQuote()
    {
        static::bootKernel();

        $container = static::$kernel->getContainer();

        $quoteClass = 'CSBill\QuoteBundle\Entity\Quote';
        $this
            ->entityManager
            ->expects($this->at(0))
            ->method('getClassMetadata')
            ->with($quoteClass)
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata($quoteClass)));

        $invoiceClass = 'CSBill\InvoiceBundle\Entity\Invoice';
        $this
            ->entityManager
            ->expects($this->at(1))
            ->method('getClassMetadata')
            ->with($invoiceClass)
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata($invoiceClass)));

        $this
            ->entityManager
            ->expects($this->at(2))
            ->method('getClassMetadata')
            ->with($quoteClass)
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata($quoteClass)));


        $this
            ->entityManager
            ->expects($this->at(3))
            ->method('getClassMetadata')
            ->with($invoiceClass)
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata($invoiceClass)));

        $this
            ->entityManager
            ->expects($this->at(4))
            ->method('getClassMetadata')
            ->with('CSBill\InvoiceBundle\Entity\Item')
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata('CSBill\InvoiceBundle\Entity\Item')));

        $this
            ->entityManager
            ->expects($this->at(5))
            ->method('getClassMetadata')
            ->with('CSBill\QuoteBundle\Entity\Item')
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata('CSBill\QuoteBundle\Entity\Item')));

        $this
            ->entityManager
            ->expects($this->at(6))
            ->method('getClassMetadata')
            ->with('CSBill\QuoteBundle\Entity\Item')
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata('CSBill\QuoteBundle\Entity\Item')));

        $this
            ->entityManager
            ->expects($this->at(7))
            ->method('getClassMetadata')
            ->with('CSBill\InvoiceBundle\Entity\Item')
            ->will($this->returnValue($container->get('doctrine')->getManager()->getClassMetadata('CSBill\InvoiceBundle\Entity\Item')));

        $entityRepository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->setMethods(array('findOneByName'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this
            ->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with('CSBillInvoiceBundle:Status')
            ->will($this->returnValue($entityRepository));

        $status = new Status();
        $status->setName('pending');

        $entityRepository
            ->expects($this->once())
            ->method('findOneByName')
            ->with('pending')
            ->will($this->returnValue($status));

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
        $this->assertSame($status, $invoice->getStatus());

        $this->assertNotSame($quote->getUuid(), $invoice->getUuid());
        $this->assertNull($invoice->getId());

        $this->assertCount(1, $invoice->getItems());

        /** @var \CSBill\InvoiceBundle\Entity\item[] $invoiceItem */
        $invoiceItem = $invoice->getItems();
        $this->assertInstanceOf('CSBill\InvoiceBundle\Entity\item', $invoiceItem[0]);

        $this->assertSame($item->getTax(), $invoiceItem[0]->getTax());
        $this->assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        $this->assertNull($invoiceItem[0]->getCreated());
        $this->assertSame($item->getPrice(), $invoiceItem[0]->getPrice());
        $this->assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }

    public function testGetCount()
    {
        $client = new Client();
        $repository = $this->getInvoiceRepositoryMock();
        $repository->expects($this->once())
            ->method('getCountByStatus')
            ->with('paid', $client)
            ->will($this->returnValue(5));

        $this->assertSame(5, $this->manager->getCount('paid', $client));
    }

    public function testgGetTotalIncome()
    {
        $client = new Client();
        $repository = $this->getInvoiceRepositoryMock();
        $repository->expects($this->once())
            ->method('getTotalIncome')
            ->with($client)
            ->will($this->returnValue(500));

        $this->assertSame(500, $this->manager->getTotalIncome($client));
    }

    public function testgGetTotalOutstanding()
    {
        $client = new Client();
        $repository = $this->getInvoiceRepositoryMock();
        $repository->expects($this->once())
            ->method('getTotalOutstanding')
            ->with($client)
            ->will($this->returnValue(150));

        $this->assertSame(150, $this->manager->getTotalOutstanding($client));
    }

    public function testMarkPaid()
    {
        $invoice = new Invoice();

        $status = new Status();
        $status->setName('paid');

        $entityRepository = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->setMethods(array('findOneBy', 'persist', 'flush'))
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $entityRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(array('name' => 'paid'))
            ->will($this->returnValue($status));

        $this
            ->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($invoice);

        $this
            ->entityManager
            ->expects($this->once())
            ->method('flush');

        $this
            ->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->will($this->returnValue($entityRepository));

        $event = new InvoicePaidEvent();
        $event->setInvoice($invoice);

        $this
            ->dispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(InvoiceEvents::INVOICE_PAID, $event);

        // Ensure paid date is empty when creating invoice
        $this->assertNull($invoice->getPaidDate());

        $this->manager->markPaid($invoice);

        $this->assertInstanceOf('DateTime', $invoice->getPaidDate());
        $this->assertSame($status, $invoice->getStatus());
    }
}
