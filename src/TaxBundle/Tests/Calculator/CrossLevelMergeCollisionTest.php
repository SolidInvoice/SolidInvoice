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
 * Before the fix, {@see TaxCalculator::mergeSummaryRows()} put line-level and
 * invoice-level rows into one merge pass. The merge key is
 * `name|rate|type|category|compound|direction|note`. A line-level row leaves
 * `direction` at {@see TaxDirection::Additive} and `note` at null, and an
 * invoice-level row sets `compound` to false. So an additive invoice-level tax
 * with no note, on the same name and rate as a non-compound line tax, made the
 * same key as the line row and merged into it - summing their bases even
 * though both levels applied their rate to the same subtotal, not to
 * additional money.
 *
 * The fix (architectural review §4, preferred remedy) merges each level in
 * its own pass and concatenates the results, so a line-level row and an
 * invoice-level row with the same name/rate stay two rows. Each row's own
 * base is then correct on its own terms, and {@see
 * \SolidInvoice\TaxBundle\Twig\Extension\TaxBreakdownExtension::isInvoiceLevelRow()}
 * - which already re-splits rows by level for rendering - sorts them apart
 * the same way it always has.
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

        $rows = $this->summaryRowsNamed($result->summaryRows, 'VAT 18%');

        // One row per level: the line-level row and the invoice-level row are
        // different BG-23 groups, so they no longer collapse into one.
        self::assertCount(2, $rows, 'Expected one line-level row and one invoice-level row named "VAT 18%"');

        // The client pays 18% one time per level, so 36000 total is correct.
        $totalAmount = BigDecimal::zero();
        foreach ($rows as $row) {
            $totalAmount = $totalAmount->plus($row->amount);
        }

        self::assertTrue(
            $totalAmount->isEqualTo(BigDecimal::of(36000)),
            sprintf('Expected amount 36000 across both rows, got %s', $totalAmount),
        );

        // Each level applied its rate to the same 100000 subtotal. Neither
        // row's base may be doubled by picking up the other level's base.
        foreach ($rows as $row) {
            self::assertTrue(
                $row->taxableBase->isEqualTo(BigDecimal::of(100000)),
                sprintf(
                    'Expected taxable base 100000 (the subtotal the rate was applied to), got %s. '
                    . "A base of 200000 means the row picked up both levels' bases.",
                    $row->taxableBase,
                ),
            );
        }
    }

    /**
     * The same collision with a second line at the same rate, so the
     * line-level side of the merge (two lines, one row, summed base) is
     * itself exercised alongside the invoice-level row - the defect scales
     * with the number of lines rather than being specific to one line.
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
        $rows = $this->summaryRowsNamed($result->summaryRows, 'VAT 18%');

        self::assertCount(2, $rows, 'Expected one line-level row and one invoice-level row named "VAT 18%"');

        foreach ($rows as $row) {
            self::assertTrue(
                $row->taxableBase->isEqualTo(BigDecimal::of(100000)),
                sprintf('Expected taxable base 100000, got %s', $row->taxableBase),
            );
        }
    }

    /**
     * @param list<TaxSummaryRow> $rows
     * @return list<TaxSummaryRow>
     */
    private function summaryRowsNamed(array $rows, string $name): array
    {
        return array_values(array_filter(
            $rows,
            static fn (TaxSummaryRow $row): bool => $row->name === $name,
        ));
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
