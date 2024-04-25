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

use Symfony\Component\Uid\Ulid;

final class UlidGenerator implements IdGeneratorInterface
{
    public static function getName(): string
    {
        return 'ulid';
    }

    public function getConfigurationFormType(): ?string
    {
        return null;
    }

    public function generate(object $entity, array $options): string
    {
        return (new Ulid())->toBase32();
    }
}
