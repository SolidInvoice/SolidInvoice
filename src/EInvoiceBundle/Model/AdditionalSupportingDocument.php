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
 * An additional document supporting the invoice (BG-24), e.g. a timesheet or a delivery note.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Model\AdditionalSupportingDocumentTest
 */
final readonly class AdditionalSupportingDocument
{
    public function __construct(
        /**
         * BT-122. The document's reference.
         */
        public string $reference,
        /**
         * BT-123. A description of the document.
         */
        public ?string $description = null,
        /**
         * BT-124. A URI where the document can be retrieved.
         */
        public ?string $externalUri = null,
        /**
         * BT-125. The document content itself, base64 encoded.
         */
        public ?string $attachedDocument = null,
        /**
         * BT-125-1. The MIME type of the attached document.
         */
        public ?string $mimeCode = null,
        /**
         * BT-125-2. The attached document's filename.
         */
        public ?string $filename = null,
    ) {
    }
}
