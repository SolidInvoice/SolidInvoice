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
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

class InvoiceManagerTest extends KernelTestCase
{
    use MockeryPHPUnitIntegration;

    private InvoiceManager $manager;

    protected function setUp(): void
    {
        $entityManager = M::mock(EntityManagerInterface::class);
        $doctrine = M::mock(ManagerRegistry::class, ['getManager' => $entityManager]);
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

        $config = $this->createMock(SystemConfig::class);
        $config->method('get')
            ->willReturn('generator');

        $this->manager = new InvoiceManager(
            $doctrine,
            new EventDispatcher(),
            $stateMachine,
            $notification,
            new BillingIdGenerator(
                new ServiceLocator(['generator' => fn () => $this->createMock(IdGeneratorInterface::class)]),
                $config
            )
        );

        $entityManager
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

        $line = new Line();
        $line->setTax($tax);
        $line->setDescription('Line Description');
        $line->setCreated(new DateTime('now'));
        $line->setPrice(120);
        $line->setQty(10);
        $line->setTotal(120 * 10);

        $quote = new Quote();
        $quote->setBaseTotal(123);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $quote->setDiscount($discount);
        $quote->setNotes('Notes');
        $quote->setTax(432);
        $quote->setTerms('Terms');
        $quote->setTotal(987);
        $quote->setClient($client);
        $quote->addLine($line);
        $quote->setCompany(new Company());

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

        self::assertCount(1, $invoice->getLines());

        $invoiceLine = $invoice->getLines();
        self::assertInstanceOf(InvoiceLine::class, $invoiceLine[0]);

        self::assertSame($line->getTax(), $invoiceLine[0]->getTax());
        self::assertSame($line->getDescription(), $invoiceLine[0]->getDescription());
        self::assertInstanceOf(DateTime::class, $invoiceLine[0]->getCreated());
        self::assertEquals($line->getPrice(), $invoiceLine[0]->getPrice());
        self::assertSame($line->getQty(), $invoiceLine[0]->getQty());
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

        $line = new RecurringInvoiceLine();
        $line->setTax($tax);
        $line->setDescription('Line Description {day} {day_name} {month} {year}');
        $line->setCreated(new DateTime('now'));
        $line->setPrice(120);
        $line->setQty(10);
        $line->setTotal(120 * 10);

        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setBaseTotal(123);
        $discount = new Discount();
        $discount->setType(Discount::TYPE_PERCENTAGE);
        $discount->setValue(12);
        $recurringInvoice->setDiscount($discount);
        $recurringInvoice->setNotes('Notes');
        $recurringInvoice->setTax(432);
        $recurringInvoice->setTerms('Terms');
        $recurringInvoice->setTotal(987);
        $recurringInvoice->setClient($client);
        $recurringInvoice->addLine($line);
        $recurringInvoice->setFrequency('* 0 0 * *');
        $recurringInvoice->setCompany(new Company());

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

        self::assertCount(1, $invoice->getLines());

        $invoiceLine = $invoice->getLines();
        self::assertInstanceOf(InvoiceLine::class, $invoiceLine[0]);

        self::assertSame($line->getTax(), $invoiceLine[0]->getTax());
        self::assertSame('Line Description ' . date('j l F Y'), $invoiceLine[0]->getDescription());
        self::assertInstanceOf(DateTime::class, $invoiceLine[0]->getCreated());
        self::assertEquals($line->getPrice(), $invoiceLine[0]->getPrice());
        self::assertSame($line->getQty(), $invoiceLine[0]->getQty());
    }
}
