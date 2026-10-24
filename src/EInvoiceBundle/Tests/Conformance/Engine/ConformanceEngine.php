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

use SolidInvoice\EInvoiceBundle\Tests\Conformance\Case\Profile;

/**
 * The seam between the conformance harness and a real validation engine. No implementation
 * exists yet: ConformanceEngineFactory::create() returns null until SOL-108 builds
 * SolidInvoice\EInvoiceBundle\Validator\ValidatorInterface and an adapter onto this interface.
 */
interface ConformanceEngine
{
    /**
     * $withXsd is off for a RuleCase fragment (it is not schema-valid by construction) and on
     * for an InstanceCase whole document.
     */
    public function validate(string $document, Profile $profile, bool $withXsd): ConformanceReport;
}
