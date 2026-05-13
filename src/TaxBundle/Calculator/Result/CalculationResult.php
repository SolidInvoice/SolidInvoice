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

namespace SolidInvoice\TaxBundle\Calculator\Result;

use Brick\Math\BigDecimal;

/**
 * Aggregate result of running the orchestrator across an invoice/quote.
 *
 * - {@see $subTotal} is the sum of line subtotals (tax-exclusive).
 * - {@see $totalLineTax} is the sum of all line-level tax components.
 * - {@see $invoiceLevelBreakdown} contains invoice-wide taxes (US-008 lands the math).
 * - {@see $total} is the gross total before discount.
 * - {@see $summaryRows} aggregates {@see TaxSummaryRow} entries across all lines plus
 *   any invoice-level rows, with rows merged by (name, rate, type, category, compound).
 *
 * @phpstan-type LineBreakdowns array<int, LineBreakdown>
 */
final readonly class CalculationResult
{
    /**
     * @param LineBreakdowns $lineBreakdowns
     * @param list<TaxSummaryRow> $summaryRows
     */
    public function __construct(
        public BigDecimal $subTotal,
        public BigDecimal $totalLineTax,
        public BigDecimal $total,
        public array $lineBreakdowns,
        public InvoiceLevelBreakdown $invoiceLevelBreakdown,
        public array $summaryRows,
    ) {
    }

    public function getTotalTax(): BigDecimal
    {
        return $this->totalLineTax->plus($this->invoiceLevelBreakdown->totalInvoiceLevelTax);
    }
}
