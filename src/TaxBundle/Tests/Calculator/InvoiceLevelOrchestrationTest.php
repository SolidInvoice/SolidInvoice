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
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Calculator\InvoiceTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\LineTaxCalculator;
use SolidInvoice\TaxBundle\Calculator\TaxCalculator;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
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
