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

/**
 * Payment card information (BG-18).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\CardInformationTest
 */
final readonly class CardInformation
{
    public function __construct(
        /**
         * BT-87. The primary account number, masked to its last 4-6 digits.
         */
        public string $primaryAccountNumber,
        /**
         * BT-88. The name of the card holder.
         */
        public ?string $holderName = null,
    ) {
    }
}
