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

use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\Verdict;

/**
 * What a ConformanceEngine returns for one validation run. RuleCase ignores the verdict: a
 * fragment never produces a meaningful document-level outcome.
 */
final readonly class ConformanceReport
{
    /**
     * @param list<ConformanceViolation> $violations
     */
    public function __construct(
        public Verdict $verdict,
        public array $violations,
    ) {
    }
}
