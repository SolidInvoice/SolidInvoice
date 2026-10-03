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
 * A free-text note on the invoice header (BG-1).
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\InvoiceNoteTest
 */
final readonly class InvoiceNote
{
    public function __construct(
        /**
         * BT-22. The note text itself.
         */
        public string $note,
        /**
         * BT-21. The subject code classifying the note, e.g. a UNTDID 4451 code.
         */
        public ?string $subjectCode = null,
    ) {
    }
}
