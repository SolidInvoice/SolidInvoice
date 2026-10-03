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
 * An allowance. Shared by the document-level group (BG-20) and the line-level group (BG-27).
 * A line-level allowance carries no VAT category or rate of its own — those are document-level
 * only, so `vatCategoryCode` and `vatRate` stay null for a line-level instance.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\AllowanceTest
 */
final readonly class Allowance
{
    public function __construct(
        /**
         * BT-92 (document level) / BT-136 (line level). The allowance amount.
         */
        public BigDecimal $amount,
        /**
         * BT-93 (document level) / BT-137 (line level). The base amount the allowance is calculated from.
         */
        public ?BigDecimal $baseAmount = null,
        /**
         * BT-94 (document level) / BT-138 (line level). The percentage the allowance is calculated as, where applicable.
         */
        public ?BigDecimal $percentage = null,
        /**
         * BT-95. The VAT category of the allowance. Document level only.
         */
        public ?VatCategoryCode $vatCategoryCode = null,
        /**
         * BT-96. The VAT rate of the allowance. Document level only.
         */
        public ?BigDecimal $vatRate = null,
        /**
         * BT-97 (document level) / BT-139 (line level). The reason for the allowance, as free text.
         */
        public ?string $reason = null,
        /**
         * BT-98 (document level) / BT-140 (line level). A coded reason for the allowance.
         */
        public ?string $reasonCode = null,
    ) {
    }
}
