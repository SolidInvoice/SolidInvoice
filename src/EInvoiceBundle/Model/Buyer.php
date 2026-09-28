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
 * The buyer party (BG-7).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\BuyerTest
 */
final readonly class Buyer
{
    public function __construct(
        /**
         * BT-44. The buyer's name.
         */
        public string $name,
        /**
         * BT-45. The buyer's trading name, where it differs from BT-44.
         */
        public ?string $tradingName = null,
        /**
         * BT-46/46-1. The buyer's identifier.
         */
        public ?Identifier $identifier = null,
        /**
         * BT-47/47-1. The buyer's legal registration identifier.
         */
        public ?Identifier $legalRegistrationIdentifier = null,
        /**
         * BT-48. The buyer's VAT identifier.
         */
        public ?string $vatIdentifier = null,
        /**
         * BT-49/49-1. The buyer's electronic address, used to route the document.
         */
        public ?Identifier $electronicAddress = null,
        /**
         * BG-8 (BT-50…BT-55 via PostalAddress). The buyer's postal address.
         */
        public ?PostalAddress $address = null,
        /**
         * BG-9 (BT-56…BT-58 via Contact). The buyer's contact point.
         */
        public ?Contact $contact = null,
    ) {
    }
}
