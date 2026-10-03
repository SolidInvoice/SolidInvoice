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

namespace SolidInvoice\EInvoiceBundle\Tests\Mapper;

use Carbon\CarbonImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\EInvoiceBundle\Enum\InvoiceTypeCode;
use SolidInvoice\EInvoiceBundle\Mapper\BuyerMapper;
use SolidInvoice\EInvoiceBundle\Mapper\InvoiceMapper;
use SolidInvoice\EInvoiceBundle\Mapper\MinorUnitConverter;
use SolidInvoice\EInvoiceBundle\Mapper\SellerMapper;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class InvoiceMapperTest extends KernelTestCase
{
    use EnsureApplicationInstalled;

    private function mapper(): InvoiceMapper
    {
        $systemConfig = self::getContainer()->get(SystemConfig::class);

        return new InvoiceMapper(
            new SellerMapper($systemConfig),
            new BuyerMapper(),
            new MinorUnitConverter(new CurrencyScale()),
            $systemConfig,
        );
    }

    public function testMapsHeaderSellerAndBuyer(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'name' => 'Acme Ltd',
            'currencyCode' => 'EUR',
        ]);

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'company' => $this->company,
                'client' => $client,
                'due' => CarbonImmutable::parse('2026-11-30'),
                'invoiceDate' => CarbonImmutable::parse('2026-10-01'),
                'terms' => 'Net 30',
                'notes' => 'Thank you for your business',
                'total' => 12345,
                'baseTotal' => 10000,
                'tax' => 2345,
                'balance' => 345,
            ]);
        $invoice->setInvoiceId('INV-2026-0001');

        $eInvoice = $this->mapper()->map($invoice);

        self::assertSame('INV-2026-0001', $eInvoice->invoiceNumber);
        self::assertEquals(CarbonImmutable::parse('2026-10-01'), $eInvoice->issueDate);
        self::assertSame(InvoiceTypeCode::CommercialInvoice, $eInvoice->invoiceTypeCode);
        self::assertSame('EUR', $eInvoice->currencyCode);
        self::assertEquals(CarbonImmutable::parse('2026-11-30'), $eInvoice->dueDate);
        self::assertSame('Net 30', $eInvoice->paymentTerms);
        self::assertCount(1, $eInvoice->notes);
        self::assertSame('Thank you for your business', $eInvoice->notes[0]->note);
        self::assertNull($eInvoice->notes[0]->subjectCode);

        self::assertSame('Acme Ltd', $eInvoice->buyer->name);

        self::assertSame('100.00', (string) $eInvoice->documentTotals->sumOfLineNetAmounts);
        self::assertSame('100.00', (string) $eInvoice->documentTotals->totalWithoutVat);
        self::assertSame('23.45', (string) $eInvoice->documentTotals->vatAmount);
        self::assertSame('123.45', (string) $eInvoice->documentTotals->totalWithVat);
        self::assertSame('120.00', (string) $eInvoice->documentTotals->paidAmount);
        self::assertSame('3.45', (string) $eInvoice->documentTotals->amountDueForPayment);
        self::assertNull($eInvoice->documentTotals->sumOfAllowances);
        self::assertNull($eInvoice->documentTotals->sumOfCharges);

        self::assertSame([], $eInvoice->invoiceLines);
        self::assertSame([], $eInvoice->vatBreakdowns);
        self::assertSame([], $eInvoice->allowances);
    }

    public function testNoNotesYieldsEmptyNoteList(): void
    {
        $client = ClientFactory::createOne([
            'company' => $this->company,
            'currencyCode' => 'USD',
        ]);

        /** @var Invoice $invoice */
        $invoice = InvoiceFactory::new()
            ->withoutPersisting()
            ->create([
                'company' => $this->company,
                'client' => $client,
                'notes' => null,
                'due' => null,
                'total' => 0,
                'baseTotal' => 0,
                'tax' => 0,
                'balance' => 0,
            ]);

        $eInvoice = $this->mapper()->map($invoice);

        self::assertSame([], $eInvoice->notes);
        self::assertNull($eInvoice->dueDate);
    }
}
