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
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Calculator\LineTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\Rounder;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\RoundingStrategy;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;

final class LineTaxCalculatorTest extends TestCase
{
    private LineTaxCalculator $calculator;

    private Rounder $rounder;

    protected function setUp(): void
    {
        $this->calculator = new LineTaxCalculator();
        $this->rounder = new Rounder(RoundingStrategy::HalfEven);
    }

    public function testLineWithoutTaxes(): void
    {
        $line = $this->makeLine(price: 15000, qty: 2);

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineSubtotal->isEqualTo(BigDecimal::of(30000)));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(30000)));
        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::zero()));
        self::assertSame([], $breakdown->taxRows);
    }

    public function testInclusiveTaxExtractsTheRateFromTheGross(): void
    {
        $line = $this->makeLine(price: 15000, qty: 2);
        $line->addTax($this->lineTax(name: 'VAT', rate: '20.0000', type: TaxType::Inclusive));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineSubtotal->isEqualTo(BigDecimal::of('25000.00')));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(30000)));
        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::of('5000.00')));
        self::assertCount(1, $breakdown->taxRows);
    }

    public function testExclusiveTaxAddsThePercentageOnTopOfTheSubtotal(): void
    {
        $line = $this->makeLine(price: 15000, qty: 2);
        $line->addTax($this->lineTax(name: 'GST', rate: '20.0000', type: TaxType::Exclusive));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineSubtotal->isEqualTo(BigDecimal::of(30000)));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(36000)));
        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::of(6000)));
    }

    public function testFlatRateAddsAFixedAmountInMinorUnits(): void
    {
        $line = $this->makeLine(price: 15000, qty: 2);
        $line->addTax($this->lineTax(name: 'Stamp', rate: '2.0000', type: TaxType::FlatRate));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineSubtotal->isEqualTo(BigDecimal::of(30000)));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(30200)));
        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::of(200)));
    }

    public function testExemptCategoryIsSkippedEntirely(): void
    {
        $line = $this->makeLine(price: 15000, qty: 2);
        $line->addTax($this->lineTax(
            name: 'NoTax',
            rate: '20.0000',
            type: TaxType::Exclusive,
            category: TaxCategory::Exempt,
        ));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::zero()));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(30000)));
        self::assertSame([], $breakdown->taxRows);
    }

    public function testZeroRatedCategoryRecordsAZeroAmountRow(): void
    {
        $line = $this->makeLine(price: 15000, qty: 2);
        $line->addTax($this->lineTax(
            name: 'ZeroVAT',
            rate: '0.0000',
            type: TaxType::Exclusive,
            category: TaxCategory::ZeroRated,
        ));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::zero()));
        self::assertCount(1, $breakdown->taxRows);
        self::assertSame(TaxCategory::ZeroRated, $breakdown->taxRows[0]->category);
        self::assertTrue($breakdown->taxRows[0]->amount->isEqualTo(BigDecimal::zero()));
    }

    public function testOutOfScopeCategoryRecordsAZeroAmountRow(): void
    {
        $line = $this->makeLine(price: 5000, qty: 1);
        $line->addTax($this->lineTax(
            name: 'OOS',
            rate: '15.0000',
            type: TaxType::Exclusive,
            category: TaxCategory::OutOfScope,
        ));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::zero()));
        self::assertSame(TaxCategory::OutOfScope, $breakdown->taxRows[0]->category);
    }

    public function testReverseChargeCategoryRecordsAZeroAmountRow(): void
    {
        $line = $this->makeLine(price: 10000, qty: 1);
        $line->addTax($this->lineTax(
            name: 'RC-VAT',
            rate: '20.0000',
            type: TaxType::Exclusive,
            category: TaxCategory::ReverseCharge,
        ));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::zero()));
        self::assertSame(TaxCategory::ReverseCharge, $breakdown->taxRows[0]->category);
    }

    /**
     * Quebec: 5% GST + 9.975% QST, where QST compounds on (subtotal + GST).
     */
    public function testCompoundExclusiveTaxStacksOnTopOfNonCompoundTaxes(): void
    {
        $line = $this->makeLine(price: 10000, qty: 1);
        $line->addTax($this->lineTax(name: 'GST', rate: '5.0000', type: TaxType::Exclusive, sequence: 0));
        $line->addTax($this->lineTax(name: 'QST', rate: '9.9750', type: TaxType::Exclusive, compound: true, sequence: 1));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        // GST = 10000 * 5% = 500
        // QST = (10000 + 500) * 9.975% = 1047.375 -> 1047 (HalfEven)
        self::assertTrue($breakdown->lineSubtotal->isEqualTo(BigDecimal::of(10000)));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(11547)));
        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::of(1547)));
    }

    /**
     * India GST scenario: CGST + SGST applied independently on the same base.
     */
    public function testTwoNonCompoundExclusiveTaxesApplyIndependentlyOnSubtotal(): void
    {
        $line = $this->makeLine(price: 100000, qty: 1);
        $line->addTax($this->lineTax(name: 'CGST', rate: '9.0000', type: TaxType::Exclusive, sequence: 0));
        $line->addTax($this->lineTax(name: 'SGST', rate: '9.0000', type: TaxType::Exclusive, sequence: 1));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        // 100000 * 9% = 9000 each; total tax = 18000; line total = 118000
        self::assertTrue($breakdown->lineSubtotal->isEqualTo(BigDecimal::of(100000)));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(118000)));
        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::of(18000)));
        self::assertCount(2, $breakdown->taxRows);
    }

    public function testExclusiveTaxRoundsToHalfEvenAtTheCent(): void
    {
        $line = $this->makeLine(price: 332, qty: 1);
        $line->addTax($this->lineTax(name: 'VAT', rate: '21.0000', type: TaxType::Exclusive));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        // 332 * 0.21 = 69.72 -> 70 (HalfEven)
        self::assertTrue($breakdown->lineTax->isEqualTo(BigDecimal::of(70)));
        self::assertTrue($breakdown->lineTotal->isEqualTo(BigDecimal::of(402)));
    }

    public function testTaxRowsArePopulatedWithSnapshotMetadata(): void
    {
        $line = $this->makeLine(price: 10000, qty: 1);
        $line->addTax($this->lineTax(name: 'VAT', rate: '20.0000', type: TaxType::Exclusive, sequence: 7));

        $breakdown = $this->calculator->calculateLine($line, $this->rounder);

        self::assertCount(1, $breakdown->taxRows);
        $row = $breakdown->taxRows[0];
        self::assertSame('VAT', $row->name);
        self::assertSame('20.0000', $row->rate);
        self::assertSame(TaxType::Exclusive, $row->type);
        self::assertSame(TaxCategory::Standard, $row->category);
        self::assertSame(7, $row->sequence);
        self::assertFalse($row->compound);
    }

    private function makeLine(int $price, float $qty): Line
    {
        $line = new Line();
        $line->setPrice($price);
        $line->setQty($qty);
        $line->updateTotal();

        return $line;
    }

    private function lineTax(
        string $name,
        string $rate,
        TaxType $type,
        TaxCategory $category = TaxCategory::Standard,
        bool $compound = false,
        int $sequence = 0,
    ): LineTax {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot($name);
        $lineTax->setRateSnapshot($rate);
        $lineTax->setTypeSnapshot($type);
        $lineTax->setCategorySnapshot($category);
        $lineTax->setCompound($compound);
        $lineTax->setSequence($sequence);

        return $lineTax;
    }
}
