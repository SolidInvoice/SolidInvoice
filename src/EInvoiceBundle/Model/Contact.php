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
 * A contact point. Shared by BG-6 (seller contact) and BG-9 (buyer contact).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\ContactTest
 */
final readonly class Contact
{
    public function __construct(
        /**
         * BT-41 (seller) / BT-56 (buyer).
         */
        public ?string $name = null,
        /**
         * BT-42 (seller) / BT-57 (buyer).
         */
        public ?string $telephone = null,
        /**
         * BT-43 (seller) / BT-58 (buyer).
         */
        public ?string $email = null,
    ) {
    }
}
