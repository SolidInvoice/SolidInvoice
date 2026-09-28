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
 * The seller party (BG-4).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\SellerTest
 */
final readonly class Seller
{
    public function __construct(
        /**
         * BT-27. The seller's name.
         */
        public string $name,
        /**
         * BT-28. The seller's trading name, where it differs from BT-27.
         */
        public ?string $tradingName = null,
        /**
         * BT-29/29-1. The seller's identifiers. A seller can carry more than one, each under a
         * different identification scheme.
         *
         * @var list<Identifier>
         */
        public array $identifiers = [],
        /**
         * BT-30/30-1. The seller's legal registration identifier.
         */
        public ?Identifier $legalRegistrationIdentifier = null,
        /**
         * BT-31. The seller's VAT identifier.
         */
        public ?string $vatIdentifier = null,
        /**
         * BT-32. The seller's tax registration identifier, where it is not a VAT identifier.
         */
        public ?string $taxRegistrationIdentifier = null,
        /**
         * BT-33. Additional legal information about the seller.
         */
        public ?string $additionalLegalInformation = null,
        /**
         * BT-34/34-1. The seller's electronic address, used to route the document.
         */
        public ?Identifier $electronicAddress = null,
        /**
         * BG-5 (BT-35…BT-40 via PostalAddress). The seller's postal address.
         */
        public ?PostalAddress $address = null,
        /**
         * BG-6 (BT-41…BT-43 via Contact). The seller's contact point.
         */
        public ?Contact $contact = null,
    ) {
    }
}
