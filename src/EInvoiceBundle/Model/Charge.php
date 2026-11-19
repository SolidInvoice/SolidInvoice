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
 * A charge. Shared by the document-level group (BG-21) and the line-level group (BG-28).
 * A line-level charge carries no VAT category or rate of its own — those are document-level
 * only, so `vatCategoryCode` and `vatRate` stay null for a line-level instance.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\ChargeTest
 */
final readonly class Charge
{
    public function __construct(
        /**
         * BT-99 (document level) / BT-141 (line level). The charge amount.
         */
        public BigDecimal $amount,
        /**
         * BT-100 (document level) / BT-142 (line level). The base amount the charge is calculated from.
         */
        public ?BigDecimal $baseAmount = null,
        /**
         * BT-101 (document level) / BT-143 (line level). The percentage the charge is calculated as, where applicable.
         */
        public ?BigDecimal $percentage = null,
        /**
         * BT-102. The VAT category of the charge. Document level only.
         */
        public ?VatCategoryCode $vatCategoryCode = null,
        /**
         * BT-103. The VAT rate of the charge. Document level only.
         */
        public ?BigDecimal $vatRate = null,
        /**
         * BT-104 (document level) / BT-144 (line level). The reason for the charge, as free text.
         */
        public ?string $reason = null,
        /**
         * BT-105 (document level) / BT-145 (line level). A coded reason for the charge.
         */
        public ?string $reasonCode = null,
    ) {
    }
}
