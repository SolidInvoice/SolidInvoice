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

namespace SolidInvoice\EInvoiceBundle\Validation;

use SolidInvoice\EInvoiceBundle\Enum\ValidationStage;
use SolidInvoice\EInvoiceBundle\Enum\ViolationSeverity;

final readonly class ValidationViolation
{
    public function __construct(
        public string $ruleId,
        public ViolationSeverity $severity,
        public ValidationStage $stage,
        public ?string $businessTerm,
        public ?string $xpath,
        public string $message,
    ) {
    }
}
