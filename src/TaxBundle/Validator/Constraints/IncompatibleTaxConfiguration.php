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

namespace SolidInvoice\TaxBundle\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
final class IncompatibleTaxConfiguration extends Constraint
{
    public string $inclusiveCompoundMessage = 'tax.constraint.incompatible.inclusive_compound';

    public string $flatRateCompoundMessage = 'tax.constraint.incompatible.flat_rate_compound';

    #[Override]
    public function getTargets(): string | array
    {
        return self::CLASS_CONSTRAINT;
    }
}
