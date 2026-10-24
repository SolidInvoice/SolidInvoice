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
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;
use SolidInvoice\TaxBundle\Calculator\TaxCalculator;
use SolidInvoice\TaxBundle\Entity\InvoiceTax;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;

/**
 * Regression guard for the cross-level merge collision.
 *
 * {@see TaxCalculator::mergeSummaryRows()} puts line-level and invoice-level
 * rows into one merge pass. The merge key is
 * `name|rate|type|category|compound|direction|note`. A line-level row leaves
 * `direction` at {@see TaxDirection::Additive} and `note` at null, and an
 * invoice-level row sets `compound` to false. So an additive invoice-level tax
 * with no note, on the same name and rate as a non-compound line tax, makes the
 * same key as the line row and merges into it.
 *
 * The amounts must add together, because the client pays both. The taxable
 * bases must not: the line row's base and the invoice-level row's base are the
 * same subtotal counted one time each, so the sum reports twice the money that
 * the rate was applied to.
 */
final class CrossLevelMergeCollisionTest extends TestCase
{
    private TaxCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new TaxCalculator(new LineTaxCalculator(), new InvoiceTaxCalculator());
    }

    public function testIdenticalLineAndInvoiceLevelTaxDoesNotDoubleTheTaxableBase(): void
    {
        $invoice = new Invoice();

        $line = new Line();
        $line->setPrice(100000);
        $line->setQty(1);
        $line->addTax($this->lineTax('VAT 18%', '18.0000'));

        $invoice->addLine($line);
        $invoice->addInvoiceTax($this->invoiceTax('VAT 18%', '18.0000'));

        $result = $this->calculator->calculate($invoice);

        self::assertTrue(
            $result->subTotal->isEqualTo(BigDecimal::of(100000)),
            sprintf('Expected subtotal 100000, got %s', $result->subTotal),
        );

        $row = $this->singleSummaryRow($result->summaryRows, 'VAT 18%');

        // The client pays 18% one time per level, so 36000 is correct.
        self::assertTrue(
            $row->amount->isEqualTo(BigDecimal::of(36000)),
            sprintf('Expected amount 36000, got %s', $row->amount),
        );

        // Each level applied its rate to the same 100000 subtotal. The row must
        // report that subtotal, not the sum of the two bases.
        self::assertTrue(
            $row->taxableBase->isEqualTo(BigDecimal::of(100000)),
            sprintf(
                'Expected taxable base 100000 (the subtotal the rate was applied to), got %s. '
                . 'A base of 200000 means the merge added the line-level and invoice-level bases together.',
                $row->taxableBase,
            ),
        );
    }

    /**
     * The same collision through a quote-style second line, to show the defect
     * scales with the number of lines rather than being specific to one line.
     */
    public function testTaxableBaseStaysAtTheSubtotalWithTwoLinesAtTheSameRate(): void
    {
        $invoice = new Invoice();

        foreach ([60000, 40000] as $price) {
            $line = new Line();
            $line->setPrice($price);
            $line->setQty(1);
            $line->addTax($this->lineTax('VAT 18%', '18.0000'));
            $invoice->addLine($line);
        }

        $invoice->addInvoiceTax($this->invoiceTax('VAT 18%', '18.0000'));

        $result = $this->calculator->calculate($invoice);
        $row = $this->singleSummaryRow($result->summaryRows, 'VAT 18%');

        self::assertTrue(
            $row->taxableBase->isEqualTo(BigDecimal::of(100000)),
            sprintf('Expected taxable base 100000, got %s', $row->taxableBase),
        );
    }

    /**
     * @param list<TaxSummaryRow> $rows
     */
    private function singleSummaryRow(array $rows, string $name): TaxSummaryRow
    {
        $matching = array_values(array_filter(
            $rows,
            static fn (TaxSummaryRow $row): bool => $row->name === $name,
        ));

        self::assertCount(1, $matching, sprintf('Expected exactly one summary row named "%s"', $name));

        return $matching[0];
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

    private function invoiceTax(string $name, string $rate): InvoiceTax
    {
        $invoiceTax = new InvoiceTax();
        $invoiceTax->setNameSnapshot($name);
        $invoiceTax->setRateSnapshot($rate);
        $invoiceTax->setTypeSnapshot(TaxType::Exclusive);
        $invoiceTax->setCategorySnapshot(TaxCategory::Standard);
        $invoiceTax->setDirection(TaxDirection::Additive);

        return $invoiceTax;
    }
}
