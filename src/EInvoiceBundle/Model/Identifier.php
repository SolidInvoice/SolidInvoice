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
 * A scheme-qualified identifier. Exists so that every `BT-nnn` term carrying a `-1` scheme
 * identifier sub-term (and, for a handful of terms, a `-2` scheme version sub-term) does not
 * double the property count of the party or line it belongs to.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\IdentifierTest
 */
final readonly class Identifier
{
    public function __construct(
        /**
         * The identifier itself, e.g. BT-18, BT-29, BT-30, BT-34, BT-46, BT-47, BT-49, BT-60,
         * BT-61, BT-71, BT-128, BT-157, BT-158. Not BT-155/BT-156 — those carry no scheme
         * sub-term and are a plain `?string` on `ItemInformation`.
         */
        public string $value,
        /**
         * The `-1` scheme identifier sub-term, e.g. BT-18-1, BT-29-1, BT-30-1, BT-34-1, BT-46-1,
         * BT-47-1, BT-49-1, BT-60-1, BT-61-1, BT-71-1, BT-128-1, BT-157-1, BT-158-1.
         */
        public ?string $schemeIdentifier = null,
        /**
         * The `-2` scheme version sub-term, e.g. BT-158-2.
         */
        public ?string $schemeVersion = null,
    ) {
    }
}
