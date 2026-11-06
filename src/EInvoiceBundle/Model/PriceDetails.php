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

/**
 * A line's price details (BG-29).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\PriceDetailsTest
 */
final readonly class PriceDetails
{
    public function __construct(
        /**
         * BT-146. The item's price, excluding VAT and after subtracting BT-147.
         */
        public BigDecimal $netPrice,
        /**
         * BT-147. A discount included in BT-146, where the gross price (BT-148) is also given.
         */
        public ?BigDecimal $discount = null,
        /**
         * BT-148. The item's price before subtracting BT-147.
         */
        public ?BigDecimal $grossPrice = null,
        /**
         * BT-149. The quantity BT-146 and BT-148 apply to, where it is not 1.
         */
        public ?BigDecimal $baseQuantity = null,
        /**
         * BT-150. BT-149's unit of measure code.
         */
        public ?string $baseQuantityUnitCode = null,
    ) {
    }
}
