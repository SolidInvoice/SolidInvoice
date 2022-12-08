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

namespace SolidInvoice\InvoiceBundle\Tests\Manager;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Mockery\Mock;
use Money\Currency;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\InvoiceBundle\Entity\Item as InvoiceItem;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class InvoiceManagerTest extends KernelTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var InvoiceManager
     */
    private $manager;

    /**
     * @var Mock|EntityManagerInterface
     */
    private $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = M::mock(EntityManagerInterface::class);
        $doctrine = M::mock(ManagerRegistry::class, ['getManager' => $this->entityManager]);
        $notification = M::mock(NotificationManager::class);

        $notification->shouldReceive('sendNotification')
            ->andReturn(null);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new WorkFlowSubscriber($doctrine, M::mock(NotificationManager::class)));
        $stateMachine = new StateMachine(
            new Definition(
                ['new', 'draft'],
                [new Transition('new', 'new', 'draft')]
            ),
            new MethodMarkingStore(true, 'status'),
            $dispatcher,
            'invoice'
        );

        $this->manager = new InvoiceManager($doctrine, new EventDispatcher(), $stateMachine, $notification);

        $this
            ->entityManager
            ->shouldReceive('persist', 'flush')
            ->zeroOrMoreTimes();
    }

    public function testCreateFromQuote(): void
    {
        $currency = new Currency('USD');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new Item();
        $item->setTax($tax);
        $item->setDescription('Item Description');
        $item->setCreated(new DateTime('now'));
        $item->setPrice(new Money(120, $currency));
        $item->setQty(10);
        $item->setTotal(new Money((12 * 10), $currency));

        $quote = new Quote();
        $quote->setBaseTotal(new Money(123, $currency));
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $quote->setDiscount($discount);
        $quote->setNotes('Notes');
        $quote->setTax(new Money(432, $currency));
        $quote->setTerms('Terms');
        $quote->setTotal(new Money(987, $currency));
        $quote->setClient($client);
        $quote->addItem($item);

        $invoice = $this->manager->createFromQuote($quote);

        self::assertEquals($quote->getTotal(), $invoice->getTotal());
        self::assertEquals($quote->getBaseTotal(), $invoice->getBaseTotal());
        self::assertSame($quote->getDiscount(), $invoice->getDiscount());
        self::assertSame($quote->getNotes(), $invoice->getNotes());
        self::assertSame($quote->getTerms(), $invoice->getTerms());
        self::assertEquals($quote->getTax(), $invoice->getTax());
        self::assertSame($client, $invoice->getClient());
        self::assertNull($invoice->getStatus());

        self::assertNotSame($quote->getUuid(), $invoice->getUuid());
        self::assertNull($invoice->getId());

        self::assertCount(1, $invoice->getItems());

        $invoiceItem = $invoice->getItems();
        self::assertInstanceOf(InvoiceItem::class, $invoiceItem[0]);

        self::assertSame($item->getTax(), $invoiceItem[0]->getTax());
        self::assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        self::assertInstanceOf(\DateTime::class, $invoiceItem[0]->getCreated());
        self::assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        self::assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }

    public function testCreateFromRecurring(): void
    {
        $currency = new Currency('USD');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(new DateTime('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $item = new InvoiceItem();
        $item->setTax($tax);
        $item->setDescription('Item Description {day} {day_name} {month} {year}');
        $item->setCreated(new DateTime('now'));
        $item->setPrice(new Money(120, $currency));
        $item->setQty(10);
        $item->setTotal(new Money((12 * 10), $currency));

        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setBaseTotal(new Money(123, $currency));
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $recurringInvoice->setDiscount($discount);
        $recurringInvoice->setNotes('Notes');
        $recurringInvoice->setTax(new Money(432, $currency));
        $recurringInvoice->setTerms('Terms');
        $recurringInvoice->setTotal(new Money(987, $currency));
        $recurringInvoice->setClient($client);
        $recurringInvoice->addItem($item);
        $recurringInvoice->setFrequency('* 0 0 * *');

        $invoice = $this->manager->createFromRecurring($recurringInvoice);

        self::assertEquals($recurringInvoice->getTotal(), $invoice->getTotal());
        self::assertEquals($recurringInvoice->getBaseTotal(), $invoice->getBaseTotal());
        self::assertSame($recurringInvoice->getDiscount(), $invoice->getDiscount());
        self::assertSame($recurringInvoice->getNotes(), $invoice->getNotes());
        self::assertSame($recurringInvoice->getTerms(), $invoice->getTerms());
        self::assertEquals($recurringInvoice->getTax(), $invoice->getTax());
        self::assertSame($client, $invoice->getClient());
        self::assertNull($invoice->getStatus());

        self::assertNull($invoice->getId());

        self::assertCount(1, $invoice->getItems());

        $invoiceItem = $invoice->getItems();
        self::assertInstanceOf(InvoiceItem::class, $invoiceItem[0]);

        self::assertSame($item->getTax(), $invoiceItem[0]->getTax());
        self::assertSame('Item Description ' . date('j l F Y'), $invoiceItem[0]->getDescription());
        self::assertInstanceOf(\DateTime::class, $invoiceItem[0]->getCreated());
        self::assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        self::assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }
}
