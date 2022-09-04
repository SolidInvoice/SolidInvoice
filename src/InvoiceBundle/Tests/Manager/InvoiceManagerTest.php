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

use Doctrine\Persistence\ManagerRegistry;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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

    public function setUp(): void
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

    public function testCreateFromQuote()
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

        static::assertEquals($quote->getTotal(), $invoice->getTotal());
        static::assertEquals($quote->getBaseTotal(), $invoice->getBaseTotal());
        static::assertSame($quote->getDiscount(), $invoice->getDiscount());
        static::assertSame($quote->getNotes(), $invoice->getNotes());
        static::assertSame($quote->getTerms(), $invoice->getTerms());
        static::assertEquals($quote->getTax(), $invoice->getTax());
        static::assertSame($client, $invoice->getClient());
        static::assertNull($invoice->getStatus());

        static::assertNotSame($quote->getUuid(), $invoice->getUuid());
        static::assertNull($invoice->getId());

        static::assertCount(1, $invoice->getItems());

        $invoiceItem = $invoice->getItems();
        static::assertInstanceOf(InvoiceItem::class, $invoiceItem[0]);

        static::assertSame($item->getTax(), $invoiceItem[0]->getTax());
        static::assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        static::assertInstanceOf(\DateTime::class, $invoiceItem[0]->getCreated());
        static::assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        static::assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }

    public function testCreateFromRecurring()
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
        $item->setDescription('Item Description');
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

        static::assertEquals($recurringInvoice->getTotal(), $invoice->getTotal());
        static::assertEquals($recurringInvoice->getBaseTotal(), $invoice->getBaseTotal());
        static::assertSame($recurringInvoice->getDiscount(), $invoice->getDiscount());
        static::assertSame($recurringInvoice->getNotes(), $invoice->getNotes());
        static::assertSame($recurringInvoice->getTerms(), $invoice->getTerms());
        static::assertEquals($recurringInvoice->getTax(), $invoice->getTax());
        static::assertSame($client, $invoice->getClient());
        static::assertNull($invoice->getStatus());

        static::assertNull($invoice->getId());

        static::assertCount(1, $invoice->getItems());

        $invoiceItem = $invoice->getItems();
        static::assertInstanceOf(InvoiceItem::class, $invoiceItem[0]);

        static::assertSame($item->getTax(), $invoiceItem[0]->getTax());
        static::assertSame($item->getDescription(), $invoiceItem[0]->getDescription());
        static::assertInstanceOf(\DateTime::class, $invoiceItem[0]->getCreated());
        static::assertEquals($item->getPrice(), $invoiceItem[0]->getPrice());
        static::assertSame($item->getQty(), $invoiceItem[0]->getQty());
    }
}
