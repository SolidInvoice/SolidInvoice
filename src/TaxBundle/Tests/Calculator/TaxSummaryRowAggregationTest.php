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
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;

/**
 * Covers {@see TaxCalculator::mergeSummaryRows()}: three lines at the same
 * rate must merge into one {@see \SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow}
 * whose taxableBase is the sum of the three line subtotals. Fails if
 * taxableBase is wrongly added to the merge identity key (three rows
 * survive instead of one) or wrongly left unsummed (the merged row keeps
 * only the first line's base).
 */
final class TaxSummaryRowAggregationTest extends TestCase
{
    public function testThreeLinesAtTheSameRateMergeIntoOneRowWithTheSummedBase(): void
    {
        $calculator = new TaxCalculator(new LineTaxCalculator(), new InvoiceTaxCalculator());

        $invoice = new Invoice();
        $subtotals = [10000, 20000, 30000];

        foreach ($subtotals as $price) {
            $line = new Line();
            $line->setPrice($price);
            $line->setQty(1);
            $line->addTax($this->lineTax('VAT 15%', '15.0000'));
            $invoice->addLine($line);
        }

        $result = $calculator->calculate($invoice);

        self::assertCount(1, $result->summaryRows);

        $row = $result->summaryRows[0];
        self::assertSame('VAT 15%', $row->name);
        self::assertTrue($row->taxableBase->isEqualTo(BigDecimal::of(array_sum($subtotals))));
        self::assertTrue($row->amount->isEqualTo(BigDecimal::of(1500 + 3000 + 4500)));
    }

    private function lineTax(string $name, string $rate): LineTax
    {
        $lineTax = new LineTax();
        $lineTax->setNameSnapshot($name);
        $lineTax->setRateSnapshot($rate);
        $lineTax->setTypeSnapshot(TaxType::Exclusive);
        $lineTax->setCategorySnapshot(TaxCategory::Standard);

        return $lineTax;
    }
}
