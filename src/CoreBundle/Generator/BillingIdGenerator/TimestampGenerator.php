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

/**
 * @see \SolidInvoice\CoreBundle\Tests\Generator\BillingIdGenerator\TimestampGeneratorTest
 */
final class TimestampGenerator implements IdGeneratorInterface
{
    private const string DEFAULT_FORMAT = 'YmdHis';

    public static function getName(): string
    {
        return 'timestamp';
    }

    public function getConfigurationFormType(): ?string
    {
        return null;
    }

    public function generate(object $entity, array $options): string
    {
        return date($options['format'] ?? self::DEFAULT_FORMAT);
    }
}
