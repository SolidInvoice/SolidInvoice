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
use function sprintf;

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
     * Phone numbers are too varied to match reliably, so cap the digits instead: no real phone
     * number fits in this many, while names like "Studio 2024" still do.
     */
    public const int MAX_DIGITS = 5;

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
            // The company name is shown on invoices and emails sent to clients, so spammers use it
            // to carry a link or a "call this number" message.
            // ponytail: "word.tld" also rejects names like "Acme Co.Ltd"; whitelist if users hit it
            new Assert\Regex(
                pattern: '~https?://|www\.|[\p{L}\p{N}-]+\.\p{L}{2,}\b~iu',
                message: 'core.company.name.no_url',
                match: false,
            ),
            new Assert\Regex(
                pattern: sprintf('/(?:\D*\d){%d}/u', self::MAX_DIGITS + 1),
                message: 'core.company.name.too_many_digits',
                match: false,
            ),
        ];
    }
}
