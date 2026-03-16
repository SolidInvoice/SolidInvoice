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

namespace SolidInvoice\TimeTrackingBundle\Config;

use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

final class ConfigProvider implements ProviderInterface
{
    public function provide(array $data): array
    {
        return [
            new Config(
                'time_tracking/hourly_rate',
                '0',
                'Default hourly rate in cents (e.g. 5000 = $50.00)',
                IntegerType::class,
                ['attr' => ['min' => 0]],
            ),
        ];
    }
}
