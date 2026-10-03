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
 * A whole, schema-valid billing document: a KoSIT instance, or one of the example/testfiles
 * documents shipped alongside the EN 16931 and Peppol rule corpora. Unlike RuleCase, this
 * produces a single document-level Verdict, and is validated with XSD checking on.
 */
final readonly class InstanceCase implements ConformanceCase
{
    public function __construct(
        private string $id,
        public string $document,
        public Profile $profile,
        public Verdict $expectedVerdict,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }
}
