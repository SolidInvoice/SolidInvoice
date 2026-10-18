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

namespace SolidInvoice\CoreBundle\Validator\Constraints;

use Attribute;
use Override;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY)]
final class LineDiscountWithinLineTotal extends Constraint
{
    public string $percentageRangeMessage = 'core.constraint.line_discount.percentage_range';

    public string $exceedsLineTotalMessage = 'core.constraint.line_discount.exceeds_line_total';

    public string $reasonTooLongMessage = 'core.constraint.line_discount.reason_too_long';

    #[Override]
    public function getTargets(): string
    {
        return self::PROPERTY_CONSTRAINT;
    }
}
