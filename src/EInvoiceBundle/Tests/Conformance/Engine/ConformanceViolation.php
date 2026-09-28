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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Engine;

/**
 * One rule violation reported by a ConformanceEngine. This is the harness's own value object:
 * no third-party validator type may cross into a ConformanceReport (SOL-67).
 */
final readonly class ConformanceViolation
{
    public function __construct(
        public string $ruleId,
        public Severity $severity,
        public string $message,
        public ?string $term,
    ) {
    }
}
