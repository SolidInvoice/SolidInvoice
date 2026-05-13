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
 * Result of running invoice-level (whole-document) tax calculations.
 *
 * In US-005 this is a skeleton — the actual implementation lands in US-008.
 */
final readonly class InvoiceLevelBreakdown
{
    /**
     * @param list<TaxSummaryRow> $taxRows
     */
    public function __construct(
        public BigDecimal $totalInvoiceLevelTax,
        public array $taxRows,
    ) {
    }

    public static function empty(): self
    {
        return new self(BigDecimal::zero(), []);
    }
}
