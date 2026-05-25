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

namespace SolidInvoice\TaxBundle\Tests\Calculator;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\TaxBundle\Calculator\InvoiceTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\Rounder;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\RoundingStrategy;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;

final class InvoiceTaxCalculatorTest extends TestCase
{
    private InvoiceTaxCalculator $calculator;

    private Rounder $rounder;

    protected function setUp(): void
    {
        $this->calculator = new InvoiceTaxCalculator();
        $this->rounder = new Rounder(RoundingStrategy::HalfEven);
    }

    public function testNoInvoiceTaxesReturnsEmptyBreakdown(): void
    {
        $invoice = new Invoice();

        $breakdown = $this->calculator->calculateInvoiceLevel(
            $invoice,
            BigDecimal::of(100000),
            BigDecimal::zero(),
            $this->rounder,
        );

        self::assertTrue($breakdown->totalInvoiceLevelTax->isEqualTo(BigDecimal::zero()));
        self::assertTrue($breakdown->totalWithholding->isEqualTo(BigDecimal::zero()));
        self::assertSame([], $breakdown->taxRows);
    }

    public function testDeductiveTaxAggregatesIntoWithholding(): void
    {
        // 18% line tax on $1000 (== 100000 minor units) + 10% TDS Deductive
        // → withholding = 100 (== 10000 minor units) on subTotal
        $invoice = new Invoice();
        $invoice->addInvoiceTax($this->invoiceTax(
            name: 'TDS 10%',
            rate: '10.0000',
            direction: TaxDirection::Deductive,
        ));

        $breakdown = $this->calculator->calculateInvoiceLevel(
            $invoice,
            BigDecimal::of(100000),
            BigDecimal::of(18000),
            $this->rounder,
        );

        self::assertTrue($breakdown->totalInvoiceLevelTax->isEqualTo(BigDecimal::zero()));
        self::assertTrue(
            $breakdown->totalWithholding->isEqualTo(BigDecimal::of(10000)),
            sprintf('Expected withholding 10000, got %s', $breakdown->totalWithholding),
        );
        self::assertCount(1, $breakdown->taxRows);
        self::assertSame(TaxDirection::Deductive, $breakdown->taxRows[0]->direction);
    }

    public function testFlatRateAdditiveAddsToInvoiceLevelTax(): void
    {
        // Flat $5 surcharge → 500 minor units, no line tax, total grows by 500.
        $invoice = new Invoice();
        $invoice->addInvoiceTax($this->invoiceTax(
            name: 'Handling',
            rate: '5.0000',
            direction: TaxDirection::Additive,
            taxType: TaxType::FlatRate,
        ));

        $breakdown = $this->calculator->calculateInvoiceLevel(
            $invoice,
            BigDecimal::of(10000),
            BigDecimal::zero(),
            $this->rounder,
        );

        self::assertTrue($breakdown->totalInvoiceLevelTax->isEqualTo(BigDecimal::of(500)));
        self::assertTrue($breakdown->totalWithholding->isEqualTo(BigDecimal::zero()));
    }

    public function testInformationalEmitsZeroAmountRowAndPreservesNote(): void
    {
        $invoice = new Invoice();
        $invoiceTax = $this->invoiceTax(
            name: 'Reverse-charge VAT',
            rate: '20.0000',
            direction: TaxDirection::Informational,
            category: TaxCategory::ReverseCharge,
        );
        $invoiceTax->setNote('Customer accounts for VAT under reverse charge.');

        $invoice->addInvoiceTax($invoiceTax);

        $breakdown = $this->calculator->calculateInvoiceLevel(
            $invoice,
            BigDecimal::of(100000),
            BigDecimal::zero(),
            $this->rounder,
        );

        self::assertTrue($breakdown->totalInvoiceLevelTax->isEqualTo(BigDecimal::zero()));
        self::assertTrue($breakdown->totalWithholding->isEqualTo(BigDecimal::zero()));
        self::assertCount(1, $breakdown->taxRows);
        $row = $breakdown->taxRows[0];
        self::assertTrue($row->amount->isEqualTo(BigDecimal::zero()));
        self::assertSame('Customer accounts for VAT under reverse charge.', $row->note);
        self::assertSame(TaxDirection::Informational, $row->direction);
    }

    public function testMixedAdditiveAndDeductiveAreSeparated(): void
    {
        // Add a 5% additive surcharge AND a 10% TDS withholding on $1000.
        $invoice = new Invoice();
        $invoice->addInvoiceTax($this->invoiceTax(
            name: 'Surcharge',
            rate: '5.0000',
            direction: TaxDirection::Additive,
        ));
        $invoice->addInvoiceTax($this->invoiceTax(
            name: 'TDS',
            rate: '10.0000',
            direction: TaxDirection::Deductive,
            sequence: 1,
        ));

        $breakdown = $this->calculator->calculateInvoiceLevel(
            $invoice,
            BigDecimal::of(100000),
            BigDecimal::zero(),
            $this->rounder,
        );

        self::assertTrue($breakdown->totalInvoiceLevelTax->isEqualTo(BigDecimal::of(5000)));
        self::assertTrue($breakdown->totalWithholding->isEqualTo(BigDecimal::of(10000)));
        self::assertCount(2, $breakdown->taxRows);
    }

    private function invoiceTax(
        string $name,
        string $rate,
        TaxDirection $direction,
        TaxType $taxType = TaxType::Exclusive,
        TaxCategory $category = TaxCategory::Standard,
        int $sequence = 0,
    ): InvoiceTax {
        $tax = new Tax();
        $tax->setName($name);
        $tax->setRate((float) $rate);
        $tax->setType($taxType->value);
        $tax->setCategory($category);

        $invoiceTax = new InvoiceTax();
        $invoiceTax->setTax($tax);
        $invoiceTax->setNameSnapshot($name);
        $invoiceTax->setRateSnapshot($rate);
        $invoiceTax->setCategorySnapshot($category);
        $invoiceTax->setDirection($direction);
        $invoiceTax->setSequence($sequence);

        return $invoiceTax;
    }
}
