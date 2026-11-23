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

namespace SolidInvoice\EInvoiceBundle\Model;

use Brick\Math\BigDecimal;
use SolidInvoice\EInvoiceBundle\Enum\VatCategoryCode;

/**
 * One row of the VAT breakdown (BG-23) — one entry per distinct category/rate combination.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\VatBreakdownTest
 */
final readonly class VatBreakdown
{
    public function __construct(
        /**
         * BT-117. The total VAT amount for this category/rate.
         */
        public BigDecimal $taxAmount,
        /**
         * BT-118. The VAT category this row applies to.
         */
        public VatCategoryCode $categoryCode,
        /**
         * BT-116. The taxable amount this row's VAT was calculated from. Null for a compound tax
         * row, or a document-level tax with no line attribution — the taxable base is not a
         * per-category figure in either case (design §5.5).
         */
        public ?BigDecimal $taxableAmount = null,
        /**
         * BT-119. The VAT rate for this category, expressed as a percentage.
         */
        public ?BigDecimal $rate = null,
        /**
         * BT-120. The reason this VAT category is exempt, as free text.
         */
        public ?string $exemptionReasonText = null,
        /**
         * BT-121. A coded reason this VAT category is exempt, e.g. a VATEX code.
         */
        public ?string $exemptionReasonCode = null,
    ) {
    }
}
