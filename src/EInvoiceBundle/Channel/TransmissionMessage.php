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

namespace SolidInvoice\EInvoiceBundle\Channel;

use SolidInvoice\EInvoiceBundle\Enum\ViolationSeverity;

final readonly class TransmissionMessage
{
    public function __construct(
        public ?string $code, // the platform's own code, null when it gives none
        public string $message,
        public ViolationSeverity $severity = ViolationSeverity::Error,
    ) {
    }
}
