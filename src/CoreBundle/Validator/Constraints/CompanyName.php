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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Compound;

/**
 * The rules for a company name, wherever one is set: the Company entity, onboarding and the
 * settings page all apply this, so a name cannot be valid in one place and not another.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class CompanyName extends Compound
{
    public const int MIN_LENGTH = 3;

    public const int MAX_LENGTH = 45;

    /**
     * @return Constraint[]
     */
    #[Override]
    protected function getConstraints(array $options): array
    {
        return [
            new Assert\NotBlank(message: 'core.company.name.not_blank', normalizer: 'trim'),
            new Assert\Length(
                min: self::MIN_LENGTH,
                max: self::MAX_LENGTH,
                normalizer: 'trim',
                minMessage: 'core.company.name.min_length',
                maxMessage: 'core.company.name.max_length',
            ),
        ];
    }
}
