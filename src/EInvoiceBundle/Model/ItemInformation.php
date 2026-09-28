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
 * Information about the item an invoice line invoices (BG-31).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\ItemInformationTest
 */
final readonly class ItemInformation
{
    public function __construct(
        /**
         * BT-153. The item's name.
         */
        public string $name,
        /**
         * BT-154. A description of the item.
         */
        public ?string $description = null,
        /**
         * BT-155. An identifier the seller assigned to the item.
         */
        public ?Identifier $sellerIdentifier = null,
        /**
         * BT-156. An identifier the buyer assigned to the item.
         */
        public ?Identifier $buyerIdentifier = null,
        /**
         * BT-157/157-1. A standardised identifier for the item, e.g. a GTIN.
         */
        public ?Identifier $standardIdentifier = null,
        /**
         * BT-158/158-1/158-2. A classification identifier for the item.
         */
        public ?Identifier $classificationIdentifier = null,
        /**
         * BT-159. The item's country of origin, ISO 3166-1 alpha-2.
         */
        public ?string $originCountryCode = null,
        /**
         * BG-32 (BT-160/BT-161 via ItemAttribute). Additional named attributes of the item.
         *
         * @var list<ItemAttribute>
         */
        public array $attributes = [],
    ) {
    }
}
