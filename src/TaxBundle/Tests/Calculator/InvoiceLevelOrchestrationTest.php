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
use Brick\Math\RoundingMode;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Calculator\InvoiceTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\LineTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\Result\CalculationResult;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Calculator\Rounder;
use SolidInvoice\TaxBundle\Calculator\TaxCalculator;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\RoundingStrategy;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;

/**
 * End-to-end orchestration tests that exercise the US-008 acceptance scenarios
 * via {@see TaxCalculator}, verifying that {@see CalculationResult} surfaces
 * the right withholding/payable values for the {@see TotalCalculator} to set.
 */
final class InvoiceLevelOrchestrationTest extends TestCase
{
    private TaxCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new TaxCalculator(new LineTaxCalculator(), new InvoiceTaxCalculator());
    }

    public function testTdsScenarioFromAcceptanceCriteria(): void
    {
        // $1000 line × 18% line tax + 10% TDS Deductive
        // → tax 180, total 1180, withholding 100, payable 1080 (all in major units;
        // we use minor units here: 100000, 18000, 118000, 10000, 108000).
        $invoice = new Invoice();
        $line = new Line();
        $line->setPrice(100000);
        $line->setQty(1);
        $line->addTax($this->lineTax('VAT 18%', '18.0000', TaxType::Exclusive));

        $invoice->addLine($line);

        $invoice->addInvoiceTax($this->invoiceTax('TDS 10%', '10.0000', TaxDirection::Deductive));

        $result = $this->calculator->calculate($invoice);

        self::assertTrue($result->subTotal->isEqualTo(BigDecimal::of(100000)));
        self::assertTrue($result->totalLineTax->isEqualTo(BigDecimal::of(18000)));
        self::assertTrue(
            $result->total->isEqualTo(BigDecimal::of(118000)),
            sprintf('Expected total 118000, got %s', $result->total),
        );
        self::assertTrue(
            $result->totalWithholding->isEqualTo(BigDecimal::of(10000)),
            sprintf('Expected withholding 10000, got %s', $result->totalWithholding),
        );
        self::assertTrue(
            $result->amountPayable->isEqualTo(BigDecimal::of(108000)),
            sprintf('Expected payable 108000, got %s', $result->amountPayable),
        );
    }

    public function testFlatRateAdditiveSurchargeScenarioFromAcceptanceCriteria(): void
    {
        // $100 line, no line tax, $5 FlatRate Additive surcharge → total 105.
        $invoice = new Invoice();
        $line = new Line();
        $line->setPrice(10000);
        $line->setQty(1);

        $invoice->addLine($line);

        $invoice->addInvoiceTax($this->invoiceTax(
            name: 'Handling',
            rate: '5.0000',
            direction: TaxDirection::Additive,
            taxType: TaxType::FlatRate,
        ));

        $result = $this->calculator->calculate($invoice);

        self::assertTrue($result->subTotal->isEqualTo(BigDecimal::of(10000)));
        self::assertTrue($result->totalLineTax->isEqualTo(BigDecimal::zero()));
        self::assertTrue(
            $result->total->isEqualTo(BigDecimal::of(10500)),
            sprintf('Expected total 10500, got %s', $result->total),
        );
        self::assertTrue($result->totalWithholding->isEqualTo(BigDecimal::zero()));
        self::assertTrue($result->amountPayable->isEqualTo(BigDecimal::of(10500)));
    }

    public function testReverseChargeInformationalLeavesTotalsAlone(): void
    {
        $invoice = new Invoice();
        $line = new Line();
        $line->setPrice(50000);
        $line->setQty(1);

        $invoice->addLine($line);

        $informational = $this->invoiceTax(
            name: 'Reverse Charge',
            rate: '20.0000',
            direction: TaxDirection::Informational,
            category: TaxCategory::ReverseCharge,
        );
        $informational->setNote('Customer accounts for VAT under reverse charge.');

        $invoice->addInvoiceTax($informational);

        $result = $this->calculator->calculate($invoice);

        self::assertTrue($result->total->isEqualTo(BigDecimal::of(50000)));
        self::assertTrue($result->totalWithholding->isEqualTo(BigDecimal::zero()));
        self::assertTrue($result->amountPayable->isEqualTo(BigDecimal::of(50000)));

        $row = $result->invoiceLevelBreakdown->taxRows[0];
        self::assertTrue($row->amount->isEqualTo(BigDecimal::zero()));
        self::assertSame('Customer accounts for VAT under reverse charge.', $row->note);
    }

    /**
     * Design §6, invariant 1: for Standard + Exclusive rows, amount ==
     * round(taxableBase × rate / 100), within a tolerance of one minor unit
     * per contributing line (each line rounds its own tax before the rows
     * merge).
     */
    public function testExclusivePerRowArithmeticHoldsWithinRoundingTolerance(): void
    {
        $invoice = new Invoice();
        $prices = [333, 1067, 2401];

        foreach ($prices as $price) {
            $line = new Line();
            $line->setPrice($price);
            $line->setQty(1);
            $line->addTax($this->lineTax('VAT 15%', '15.0000', TaxType::Exclusive));
            $invoice->addLine($line);
        }

        $result = $this->calculator->calculate($invoice);
        $row = $this->singleSummaryRow($result, 'VAT 15%');

        $rate = BigDecimal::of('15.0000');
        $expected = new Rounder(RoundingStrategy::HalfEven)->round(
            $row->taxableBase->multipliedBy($rate->dividedBy(100, 10, RoundingMode::HalfEven))
        );

        $diff = $row->amount->minus($expected)->abs();
        self::assertTrue(
            $diff->isLessThanOrEqualTo(BigDecimal::of(count($prices))),
            sprintf('Expected amount %s within %d of %s, diff %s', $row->amount, count($prices), $expected, $diff),
        );
    }

    /**
     * Design §6, invariant 2: for Standard + Inclusive rows, taxableBase +
     * amount equals that row's gross contribution.
     */
    public function testInclusivePerRowArithmeticEqualsGrossContribution(): void
    {
        $invoice = new Invoice();
        $line = new Line();
        $line->setPrice(30000);
        $line->setQty(1);
        $line->addTax($this->lineTax('VAT Inclusive 20%', '20.0000', TaxType::Inclusive));

        $invoice->addLine($line);

        $result = $this->calculator->calculate($invoice);
        $row = $this->singleSummaryRow($result, 'VAT Inclusive 20%');

        self::assertTrue($row->taxableBase->plus($row->amount)->isEqualTo(BigDecimal::of(30000)));
    }

    /**
     * Design §6, invariant 3: the sum of every summary row's amount equals
     * {@see CalculationResult::getTotalTax()} - already true before this
     * change, kept as a regression guard. Deductive taxes are excluded from
     * this invoice so withholding does not also land in the row sum.
     */
    public function testTotalAmountAcrossSummaryRowsEqualsGetTotalTax(): void
    {
        $invoice = new Invoice();
        $line = new Line();
        $line->setPrice(100000);
        $line->setQty(1);
        $line->addTax($this->lineTax('VAT', '18.0000', TaxType::Exclusive));

        $invoice->addLine($line);

        $invoice->addInvoiceTax($this->invoiceTax('Surcharge', '5.0000', TaxDirection::Additive));

        $result = $this->calculator->calculate($invoice);

        $sumOfRows = BigDecimal::zero();
        foreach ($result->summaryRows as $row) {
            $sumOfRows = $sumOfRows->plus($row->amount);
        }

        self::assertTrue($sumOfRows->isEqualTo($result->getTotalTax()));
    }

    /**
     * Design §6, invariant 4: "bases sum to the subtotal" only holds in the
     * restricted case where every line carries exactly one Standard,
     * non-compound, Exclusive tax and there are no invoice-level taxes.
     */
    public function testBasesSumToSubtotalInTheRestrictedCase(): void
    {
        $invoice = new Invoice();

        $line1 = new Line();
        $line1->setPrice(10000);
        $line1->setQty(1);
        $line1->addTax($this->lineTax('VAT A', '10.0000', TaxType::Exclusive));

        $invoice->addLine($line1);

        $line2 = new Line();
        $line2->setPrice(20000);
        $line2->setQty(1);
        $line2->addTax($this->lineTax('VAT B', '20.0000', TaxType::Exclusive));

        $invoice->addLine($line2);

        $result = $this->calculator->calculate($invoice);

        $sumOfBases = BigDecimal::zero();
        foreach ($result->summaryRows as $row) {
            $sumOfBases = $sumOfBases->plus($row->taxableBase);
        }

        self::assertTrue($sumOfBases->isEqualTo($result->subTotal));
    }

    private function singleSummaryRow(CalculationResult $result, string $name): TaxSummaryRow
    {
        $matching = array_values(array_filter(
            $result->summaryRows,
            static fn (TaxSummaryRow $row): bool => $row->name === $name,
        ));

        self::assertCount(1, $matching, sprintf('Expected exactly one summary row named "%s"', $name));

        return $matching[0];
    }

    private function lineTax(string $name, string $rate, TaxType $type): LineTax
    {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot($name);
        $lineTax->setRateSnapshot($rate);
        $lineTax->setTypeSnapshot($type);
        $lineTax->setCategorySnapshot(TaxCategory::Standard);

        return $lineTax;
    }

    private function invoiceTax(
        string $name,
        string $rate,
        TaxDirection $direction,
        TaxType $taxType = TaxType::Exclusive,
        TaxCategory $category = TaxCategory::Standard,
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

        return $invoiceTax;
    }
}
