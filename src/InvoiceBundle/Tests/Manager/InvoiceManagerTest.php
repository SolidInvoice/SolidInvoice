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

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use Psr\Clock\ClockInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator;
use SolidInvoice\CoreBundle\Generator\BillingIdGenerator\IdGeneratorInterface;
use SolidInvoice\CoreBundle\Repository\CustomFieldRepository;
use SolidInvoice\CoreBundle\Repository\CustomFieldValueRepository;
use SolidInvoice\CoreBundle\Service\CustomField\CustomFieldValueCopier;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceLine;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceLine;
use SolidInvoice\InvoiceBundle\Listener\WorkFlowSubscriber;
use SolidInvoice\InvoiceBundle\Manager\InvoiceManager;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\StateMachine;
use Symfony\Component\Workflow\Transition;

final class InvoiceManagerTest extends KernelTestCase
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

        $config = $this->createStub(SystemConfig::class);
        $config->method('get')
            ->willReturn('generator');

        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')
            ->willReturn(CarbonImmutable::parse('2024-01-15 10:30:00'));

        $this->manager = new InvoiceManager(
            $doctrine,
            new EventDispatcher(),
            $stateMachine,
            $notification,
            new BillingIdGenerator(
                new ServiceLocator(['generator' => fn () => $this->createStub(IdGeneratorInterface::class)]),
                $config
            ),
            $clock,
            new CustomFieldValueCopier(
                M::mock(CustomFieldRepository::class, ['findByTargetOrdered' => []]),
                M::mock(CustomFieldValueRepository::class, ['findForRecord' => []]),
                $entityManager,
            ),
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
        $client->setCreated(Carbon::parse('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $line = new Line();
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $line->addTax($lineTax);
        $line->setDescription('Line Description');
        $line->setCreated(Carbon::now());
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

        self::assertCount(1, $invoiceLine[0]->getTaxes());
        self::assertSame('VAT', $invoiceLine[0]->getTaxes()->first()->getNameSnapshot());
        self::assertSame($line->getDescription(), $invoiceLine[0]->getDescription());
        self::assertInstanceOf(DateTimeImmutable::class, $invoiceLine[0]->getCreated());
        self::assertEquals($line->getPrice(), $invoiceLine[0]->getPrice());
        self::assertSame($line->getQty(), $invoiceLine[0]->getQty());
    }

    public function testQuoteToInvoiceCopiesLineTaxSnapshotsAsNewRows(): void
    {
        $client = new Client();
        $client->setName('Test Client');

        $sourceLineTax = new LineTax();
        $sourceLineTax->setNameSnapshot('GST');
        $sourceLineTax->setRateSnapshot('5.0000');
        $sourceLineTax->setCategorySnapshot(TaxCategory::Standard);
        $sourceLineTax->setTypeSnapshot(TaxType::Exclusive);
        $sourceLineTax->setCompound(false);
        $sourceLineTax->setSequence(1);

        $line = new Line();
        $line->setDescription('Service');
        $line->setPrice(120);
        $line->setQty(1);
        $line->setTotal(120);
        $line->addTax($sourceLineTax);

        $quote = new Quote();
        $quote->setBaseTotal(120);
        $quote->setTotal(126);
        $quote->setClient($client);
        $quote->addLine($line);
        $quote->setCompany(new Company());

        $sourceInvoiceTax = new InvoiceTax();
        $sourceInvoiceTax->setNameSnapshot('TDS');
        $sourceInvoiceTax->setRateSnapshot('10.0000');
        $sourceInvoiceTax->setDirection(TaxDirection::Deductive);
        $sourceInvoiceTax->setNote('Withholding 10%');

        $quote->addInvoiceTax($sourceInvoiceTax);

        $invoice = $this->manager->createFromQuote($quote);

        // New LineTax row, not a shared reference.
        $invoiceLineTaxes = $invoice->getLines()->first()->getTaxes();
        self::assertCount(1, $invoiceLineTaxes);
        $copiedLineTax = $invoiceLineTaxes->first();
        self::assertInstanceOf(LineTax::class, $copiedLineTax);
        self::assertNotSame($sourceLineTax, $copiedLineTax);
        self::assertSame('GST', $copiedLineTax->getNameSnapshot());
        self::assertSame('5.0000', $copiedLineTax->getRateSnapshot());
        self::assertSame(TaxType::Exclusive, $copiedLineTax->getTypeSnapshot());
        self::assertSame(1, $copiedLineTax->getSequence());

        // Quote→Invoice does NOT freeze on conversion (draft invoice still mutable).
        self::assertNull($copiedLineTax->getSnapshottedAt());

        // New InvoiceTax row preserving direction + note + snapshot fields.
        self::assertCount(1, $invoice->getInvoiceTaxes());
        $copiedInvoiceTax = $invoice->getInvoiceTaxes()->first();
        self::assertNotSame($sourceInvoiceTax, $copiedInvoiceTax);
        self::assertSame('TDS', $copiedInvoiceTax->getNameSnapshot());
        self::assertSame(TaxDirection::Deductive, $copiedInvoiceTax->getDirection());
        self::assertSame('Withholding 10%', $copiedInvoiceTax->getNote());
    }

    public function testRecurringGenerationFreezesSnapshotsAtGenerationTime(): void
    {
        $client = new Client();
        $client->setName('Test Client');

        $sourceLineTax = new LineTax();
        $sourceLineTax->setNameSnapshot('VAT');
        $sourceLineTax->setRateSnapshot('20.0000');
        $sourceLineTax->setTypeSnapshot(TaxType::Exclusive);

        $line = new RecurringInvoiceLine();
        $line->setDescription('Recurring Service');
        $line->setPrice(1000);
        $line->setQty(1);
        $line->setTotal(1000);
        $line->addTax($sourceLineTax);

        $recurring = new RecurringInvoice();
        $recurring->setBaseTotal(1000);
        $recurring->setTotal(1200);
        $recurring->setClient($client);
        $recurring->addLine($line);
        $recurring->setCompany(new Company());

        $invoice = $this->manager->createFromRecurring($recurring);

        $copiedLineTax = $invoice->getLines()->first()->getTaxes()->first();
        self::assertInstanceOf(LineTax::class, $copiedLineTax);
        self::assertNotSame($sourceLineTax, $copiedLineTax);

        // Recurring generation freezes snapshots at generation time.
        self::assertNotNull($copiedLineTax->getSnapshottedAt());
        self::assertSame('VAT', $copiedLineTax->getNameSnapshot());
        self::assertSame('20.0000', $copiedLineTax->getRateSnapshot());

        // Editing the new LineTax with snapshotFrom() must be refused — frozen.
        $newRate = new Tax()
            ->setName('VAT-updated')
            ->setRate(99.0)
            ->setType(Tax::TYPE_EXCLUSIVE);
        $copiedLineTax->snapshotFrom($newRate);

        self::assertSame('VAT', $copiedLineTax->getNameSnapshot());
        self::assertSame('20.0000', $copiedLineTax->getRateSnapshot());
    }

    public function testCreateFromRecurring(): void
    {
        $currency = new Currency('USD');

        $client = new Client();
        $client->setName('Test Client');
        $client->setWebsite('http://example.com');
        $client->setCreated(Carbon::parse('NOW'));

        $tax = new Tax();
        $tax->setName('VAT');
        $tax->setRate(14.00);
        $tax->setType(Tax::TYPE_INCLUSIVE);

        $line = new RecurringInvoiceLine();
        $lineTax = new LineTax();
        $lineTax->snapshotFrom($tax);

        $line->addTax($lineTax);
        $line->setDescription('Line Description {day} {day_name} {month} {year}');
        $line->setCreated(Carbon::now());
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

        self::assertCount(1, $invoiceLine[0]->getTaxes());
        self::assertSame('VAT', $invoiceLine[0]->getTaxes()->first()->getNameSnapshot());
        self::assertSame('Line Description 15 Monday January 2024', $invoiceLine[0]->getDescription());
        self::assertInstanceOf(DateTimeImmutable::class, $invoiceLine[0]->getCreated());
        self::assertEquals($line->getPrice(), $invoiceLine[0]->getPrice());
        self::assertSame($line->getQty(), $invoiceLine[0]->getQty());
    }
}
