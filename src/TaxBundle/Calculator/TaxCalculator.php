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

namespace SolidInvoice\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Calculator\Result\CalculationResult;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;

/**
 * Orchestrates {@see LineTaxCalculator} and {@see InvoiceTaxCalculator}, returning a
 * single {@see CalculationResult} that {@see \SolidInvoice\CoreBundle\Billing\TotalCalculator}
 * uses to populate {@see BaseInvoice}/{@see Quote} totals.
 */
final readonly class TaxCalculator implements TaxCalculatorInterface
{
    public function __construct(
        private LineTaxCalculator $lineTaxCalculator,
        private InvoiceTaxCalculator $invoiceTaxCalculator,
    ) {
    }

    /**
     * @throws MathException
     */
    public function calculate(BaseInvoice | Quote $document, ?CalculationOptions $options = null): CalculationResult
    {
        $options ??= CalculationOptions::defaults();
        $rounder = new Rounder($options->rounding);

        $subTotal = BigDecimal::zero();
        $total = BigDecimal::zero();
        $totalLineTax = BigDecimal::zero();
        $lineBreakdowns = [];
        $perLineSummary = [];

        foreach ($document->getLines() as $line) {
            $line->updateTotal();

            $breakdown = $this->lineTaxCalculator->calculateLine($line, $rounder);

            $lineBreakdowns[] = $breakdown;
            $subTotal = $subTotal->plus($breakdown->lineSubtotal);
            $total = $total->plus($breakdown->lineTotal);
            $totalLineTax = $totalLineTax->plus($breakdown->lineTax);

            foreach ($breakdown->taxRows as $row) {
                $perLineSummary[] = $row;
            }
        }

        $invoiceLevel = $this->invoiceTaxCalculator->calculateInvoiceLevel(
            $document,
            $subTotal,
            $totalLineTax,
            $rounder
        );

        // Line-level and invoice-level rows are merged in separate passes, not
        // together: a line-level tax and an invoice-level tax can snapshot the
        // same name/rate and still be two different BG-23 groups, each applied
        // to its own base. Merging them into one row would make that row's base
        // meaningless - the two contributions tax the same subtotal rather than
        // adding further money to it, so neither summing nor picking one base
        // over the other is a real answer. Keeping the rows separate reports
        // each level's own, correct base and lets TaxBreakdownExtension's
        // existing isInvoiceLevelRow() split them the way it already does.
        $summaryRows = [
            ...$this->mergeSummaryRows($perLineSummary),
            ...$this->mergeSummaryRows($invoiceLevel->taxRows),
        ];

        $total = $total->plus($invoiceLevel->totalInvoiceLevelTax);

        return new CalculationResult(
            subTotal: $subTotal,
            totalLineTax: $totalLineTax,
            total: $total,
            lineBreakdowns: $lineBreakdowns,
            invoiceLevelBreakdown: $invoiceLevel,
            summaryRows: $summaryRows,
        );
    }

    /**
     * Aggregate {@see TaxSummaryRow}s with the same identity (name + rate + type +
     * category + compound flag) into a single row whose amount and taxableBase
     * are both summed.
     *
     * Call this once per level (line, invoice), never on a combined list - see
     * the comment at the call site in {@see self::calculate()}.
     *
     * @param list<TaxSummaryRow> $rows
     * @return list<TaxSummaryRow>
     *
     * @throws MathException
     */
    private function mergeSummaryRows(array $rows): array
    {
        $merged = [];

        foreach ($rows as $row) {
            $key = sprintf(
                '%s|%s|%s|%s|%d|%s|%s',
                $row->name,
                $row->rate,
                $row->type->value,
                $row->category->value,
                $row->compound ? 1 : 0,
                $row->direction->value,
                $row->note ?? '',
            );

            if (! isset($merged[$key])) {
                $merged[$key] = $row;
                continue;
            }

            $existing = $merged[$key];
            $merged[$key] = new TaxSummaryRow(
                name: $existing->name,
                rate: $existing->rate,
                category: $existing->category,
                type: $existing->type,
                compound: $existing->compound,
                amount: $existing->amount->plus($row->amount),
                taxableBase: $existing->taxableBase->plus($row->taxableBase),
                sequence: $existing->sequence,
                direction: $existing->direction,
                note: $existing->note,
            );
        }

        return array_values($merged);
    }
}
