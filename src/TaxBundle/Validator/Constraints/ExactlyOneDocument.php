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
final class ExactlyOneDocument extends Constraint
{
    public string $message = 'Exactly one of invoice, quote, or recurring invoice must be set on an InvoiceTax.';

    #[Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
