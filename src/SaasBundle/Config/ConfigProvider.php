<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\SaasBundle\Config;

use SolidInvoice\CoreBundle\Validator\Constraints\NotApplicationUrlHost;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Hostname;

final class ConfigProvider implements ProviderInterface
{
    public function provide(array $data): array
    {
        return [
            new Config(
                'system/general/hide_powered_by',
                '0',
                'Hide "Powered by SolidInvoice" text in invoices and quotes.',
                CheckboxType::class,
                ['trial_restricted' => true]
            ),
            new Config(
                'system/company/custom_domain',
                null,
                'Custom domain for this company (leave empty to use the default URL).',
                TextType::class,
                [
                    'trial_restricted' => true,
                    'constraints' => [
                        new Hostname(['requireTld' => true]),
                        new NotApplicationUrlHost(),
                    ],
                ],
            ),
        ];
    }
}
