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
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxDirection;
use SolidInvoice\TaxBundle\Enum\TaxType;

/**
 * Per-tax breakdown line, suitable for rendering on an invoice/quote summary.
 *
 * {@see $direction} and {@see $note} are only populated for invoice-level rows
 * (see {@see InvoiceLevelBreakdown}); line-level rows leave them as defaults.
 */
final readonly class TaxSummaryRow
{
    public function __construct(
        public string $name,
        public string $rate,
        public TaxCategory $category,
        public TaxType $type,
        public bool $compound,
        public BigDecimal $amount,
        public int $sequence = 0,
        public TaxDirection $direction = TaxDirection::Additive,
        public ?string $note = null,
    ) {
    }
}
