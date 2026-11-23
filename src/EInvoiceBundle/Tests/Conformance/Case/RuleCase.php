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

namespace SolidInvoice\EInvoiceBundle\Tests\Conformance\Case;

/**
 * One <test> fragment out of a VEFA testSet: an EN 16931 / Peppol document fragment built to
 * exercise exactly one rule. It is never schema-valid, fires many unrelated rules by
 * construction, and never produces a document-level Verdict. Only the rules named below are
 * asserted; every other violation the engine reports is ignored.
 */
final readonly class RuleCase implements ConformanceCase
{
    /**
     * @param list<string> $expectSuccess rules that must not fire, at any severity
     * @param list<string> $expectError rules that must fire at error (fatal) severity
     * @param list<string> $expectWarning rules that must fire at warning severity
     */
    public function __construct(
        private string $id,
        public string $document,
        public Profile $profile,
        public array $expectSuccess,
        public array $expectError,
        public array $expectWarning,
        public ?string $description,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}
