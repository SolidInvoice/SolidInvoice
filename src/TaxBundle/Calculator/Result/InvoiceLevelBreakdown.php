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
 * - {@see $totalInvoiceLevelTax} is the sum of additive invoice-level taxes.
 * - {@see $totalWithholding} is the sum of deductive (TDS) amounts that reduce
 *   the amount the client must pay.
 * - {@see $taxRows} contains a row per invoice-level tax (additive, deductive,
 *   and informational); informational rows carry {@see TaxSummaryRow::$note}.
 */
final readonly class InvoiceLevelBreakdown
{
    public BigDecimal $totalWithholding;

    /**
     * @param list<TaxSummaryRow> $taxRows
     */
    public function __construct(
        public BigDecimal $totalInvoiceLevelTax,
        public array $taxRows,
        ?BigDecimal $totalWithholding = null,
    ) {
        $this->totalWithholding = $totalWithholding ?? BigDecimal::zero();
    }

    public static function empty(): self
    {
        return new self(BigDecimal::zero(), [], BigDecimal::zero());
    }
}
