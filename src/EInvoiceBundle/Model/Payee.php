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
 * The payee (BG-10), where payment is due to a party other than the seller.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\PayeeTest
 */
final readonly class Payee
{
    public function __construct(
        /**
         * BT-59. The payee's name.
         */
        public string $name,
        /**
         * BT-60/60-1. The payee's identifier.
         */
        public ?Identifier $identifier = null,
        /**
         * BT-61/61-1. The payee's legal registration identifier.
         */
        public ?Identifier $legalRegistrationIdentifier = null,
    ) {
    }
}
