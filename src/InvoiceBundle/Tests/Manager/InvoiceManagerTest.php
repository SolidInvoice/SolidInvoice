<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Tests\Manager;

use CSBill\ClientBundle\Entity\Client;
use CSBill\InvoiceBundle\Listener\WorkFlowSubscriber;
use CSBill\InvoiceBundle\Manager\InvoiceManager;
use CSBill\QuoteBundle\Entity\Item;
use CSBill\QuoteBundle\Entity\Quote;
use CSBill\TaxBundle\Entity\Tax;
use Money\Currency;
use Money\Money;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class InvoiceManagerTest extends KernelTestCase
{
    /**
     * @var InvoiceManager
     */
    private $manager;

    /**
     * @var \Mockery\Mock
     */
    private $entityManager;

    public function setUp()
    {
        $this->entityManager = \Mockery::mock('Doctrine\ORM\EntityManagerInterface');
        $doctrine = \Mockery::mock('Doctrine\Common\Persistence\ManagerRegistry', ['getManager' => $this->entityManager]);
        $notification = \Mockery::mock('CSBill\NotificationBundle\Notification\NotificationManager');

        $notification->shouldReceive('sendNotification')
            ->andReturn(null);


        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new WorkFlowSubscriber($doctrine));
        $stateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new SingleStateMarkingStore('status'),
            $dispatcher,
            'invoice'
        );

        $this->manager = new InvoiceManager($doctrine, new EventDispatcher(), $stateMachine, $notification);

        $this
            ->entityManager
            ->shouldReceive('persist', 'flush')
            ->zeroOrMoreTimes();
    }

    public function testCreateFromQuote()
    {
        $currency = new Currency('USD');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new \DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Item();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new \DateTime('now'));
        $item->setPrice(new Money(120, $currency));
        $item->setQty(10);
        $item->setTotal(new Money((12 * 10), $currency));

        $quote = new Quote();
        $quote->setBaseTotal(new Money(123, $currency));
        $quote->setDiscount(12);
        $quote->setNotes('Notes');
        $quote->setTax(new Money(432, $currency));
        $quote->setTerms('Terms');
        $quote->setTotal(new Money(987, $currency));
        $quote->setClient($client);
        $quote->addItem($item);

        $invoice = $this->manager->createFromQuote($quote);

        $this->assertEquals($quote->getTotal(), $invoice->getTotal());
        $this->assertEquals($quote->getBaseTotal(), $invoice->getBaseTotal());
        $this->assertSame($quote->getDiscount(), $invoice->getDiscount());
        $this->assertSame($quote->getNotes(), $invoice->getNotes());
        $this->assertSame($quote->getTerms(), $invoice->getTerms());
        $this->assertEquals($quote->getTax(), $invoice->getTax());
        $this->assertSame($client, $invoice->getClient());
        $this->assertNull($invoice->getStatus());

        $this->assertNotSame($quote->getUuid(), $invoice->getUuid());
        $this->assertNull($invoice->getId());

        $this->assertCount(1, $invoice->getItems());

        /** @var \CSBill\InvoiceBundle\Entity\item[] $invoiceItem */
        $invoiceItem = $invoice->getItems();
        $this->assertInstanceOf('CSBill\InvoiceBundle\Entity\item', $invoiceItem[0]);

        $this->assertSame($item->getTax(), $invoiceItem[0]->getTax());
        $this->assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        $this->assertInstanceOf('DateTime', $invoiceItem[0]->getCreated());
        $this->assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        $this->assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }
}
