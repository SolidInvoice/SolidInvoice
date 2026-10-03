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

namespace SolidInvoice\EInvoiceBundle\Mapper;

use Brick\Math\BigDecimal;
use Money\Currency;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;
use SolidInvoice\EInvoiceBundle\Model\VatBreakdown;
use SolidInvoice\TaxBundle\Calculator\Result\TaxSummaryRow;

/**
 * Maps BG-23 from {@see TaxSummaryRow} entries, already merged by one category/rate/type
 * combination in `TaxCalculator::mergeSummaryRows()` — one {@see VatBreakdown} per row, no
 * further merging or rate arithmetic here (design §13.1).
 *
 * BT-116 (taxable amount) is always null: {@see TaxSummaryRow} carries the tax amount only, never
 * the base it was calculated from, for any row — not only the compound/document-level cases
 * design §5.5 names. Recomputing a base from `amount` and `rate` would be the rate arithmetic
 * §13.1 forbids. Flagged on SOL-84.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Mapper\VatBreakdownMapperTest
 */
final readonly class VatBreakdownMapper
{
    public function __construct(
        private MinorUnitConverter $converter,
    ) {
    }

    /**
     * @param list<TaxSummaryRow> $summaryRows
     * @return list<VatBreakdown>
     */
    public function map(array $summaryRows, Currency $currency): array
    {
        return array_map(
            fn (TaxSummaryRow $row): VatBreakdown => new VatBreakdown(
                taxAmount: $this->converter->toMajorUnit($row->amount, $currency),
                categoryCode: VatCategoryCode::fromTaxCategory($row->category),
                rate: BigDecimal::of($row->rate),
            ),
            $summaryRows,
        );
    }
}
