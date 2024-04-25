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

namespace SolidInvoice\CoreBundle\Generator\BillingIdGenerator;

use Random\RandomException;

final class RandomNumberGenerator implements IdGeneratorInterface
{
    public const MIN_VALUE = 100000;

    public const MAX_VALUE = 999999;

    public static function getName(): string
    {
        return 'random_number';
    }

    public function getConfigurationFormType(): ?string
    {
        return null;
    }

    /**
     * @throws RandomException
     */
    public function generate(object $entity, array $options): string
    {
        return (string) random_int($options['min'] ?? self::MIN_VALUE, $options['max'] ?? self::MAX_VALUE);
    }
}
