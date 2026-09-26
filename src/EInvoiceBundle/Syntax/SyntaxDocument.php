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

namespace SolidInvoice\EInvoiceBundle\Syntax;

use SolidInvoice\EInvoiceBundle\Enum\SyntaxFormat;

final readonly class SyntaxDocument
{
    public function __construct(
        public string $payload,
        public SyntaxFormat $format,
        public string $mediaType,
        public string $filename,
    ) {
    }
}
