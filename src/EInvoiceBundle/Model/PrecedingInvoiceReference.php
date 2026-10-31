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

use DateTimeImmutable;

/**
 * A reference to a preceding invoice (BG-3), used by a credit note or corrected invoice.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\PrecedingInvoiceReferenceTest
 */
final readonly class PrecedingInvoiceReference
{
    public function __construct(
        /**
         * BT-25. The preceding invoice number.
         */
        public string $reference,
        /**
         * BT-26. The preceding invoice's issue date.
         */
        public ?DateTimeImmutable $issueDate = null,
    ) {
    }
}
