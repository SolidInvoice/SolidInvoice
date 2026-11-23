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
 * The seller's tax representative (BG-11), for a seller with a VAT obligation in a jurisdiction
 * it is not established in.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\SellerTaxRepresentativeTest
 */
final readonly class SellerTaxRepresentative
{
    public function __construct(
        /**
         * BT-62. The tax representative's name.
         */
        public string $name,
        /**
         * BT-63. The tax representative's VAT identifier.
         */
        public ?string $vatIdentifier = null,
        /**
         * BG-12 (BT-64…BT-69 via PostalAddress). The tax representative's postal address.
         */
        public ?PostalAddress $address = null,
    ) {
    }
}
