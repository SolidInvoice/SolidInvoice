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
 * A postal address. Shared by every party group that carries one: BG-5 (seller), BG-8 (buyer),
 * BG-12 (seller tax representative) and BG-15 (deliver to).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\PostalAddressTest
 */
final readonly class PostalAddress
{
    public function __construct(
        /**
         * BT-35 (seller) / BT-50 (buyer) / BT-64 (tax representative) / BT-75 (deliver to).
         */
        public ?string $addressLine1 = null,
        /**
         * BT-36 (seller) / BT-51 (buyer) / BT-65 (tax representative) / BT-76 (deliver to).
         */
        public ?string $addressLine2 = null,
        /**
         * BT-162 (seller) / BT-163 (buyer) / BT-164 (tax representative) / BT-165 (deliver to).
         */
        public ?string $addressLine3 = null,
        /**
         * BT-37 (seller) / BT-52 (buyer) / BT-66 (tax representative) / BT-77 (deliver to).
         */
        public ?string $city = null,
        /**
         * BT-38 (seller) / BT-53 (buyer) / BT-67 (tax representative) / BT-78 (deliver to).
         */
        public ?string $postCode = null,
        /**
         * BT-39 (seller) / BT-54 (buyer) / BT-68 (tax representative) / BT-79 (deliver to).
         */
        public ?string $countrySubdivision = null,
        /**
         * BT-40 (seller) / BT-55 (buyer) / BT-69 (tax representative) / BT-80 (deliver to).
         * ISO 3166-1 alpha-2, though the mapper passes the value through unvalidated (design §4.3).
         */
        public ?string $countryCode = null,
    ) {
    }
}
