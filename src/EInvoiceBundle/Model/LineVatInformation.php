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
 * A line's VAT information (BG-30).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\LineVatInformationTest
 */
final readonly class LineVatInformation
{
    public function __construct(
        /**
         * BT-151. The VAT category this line is taxed under.
         */
        public VatCategoryCode $categoryCode,
        /**
         * BT-152. The VAT rate for this line, expressed as a percentage.
         */
        public ?BigDecimal $rate = null,
    ) {
    }
}
