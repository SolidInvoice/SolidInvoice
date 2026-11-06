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
 * A named item property (BG-32), e.g. "Colour" / "Red".
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\ItemAttributeTest
 */
final readonly class ItemAttribute
{
    public function __construct(
        /**
         * BT-160. The attribute's name.
         */
        public string $name,
        /**
         * BT-161. The attribute's value.
         */
        public string $value,
    ) {
    }
}
