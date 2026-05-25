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

namespace SolidInvoice\SaasBundle\Config;

use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SaasBundle\Form\Type\CustomDomainType;
use SolidInvoice\SettingsBundle\Config\ProviderInterface;
use SolidInvoice\SettingsBundle\DTO\Config;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * @see \SolidInvoice\SaasBundle\Tests\Config\ConfigProviderTest
 */
final class ConfigProvider implements ProviderInterface
{
    /**
     * @return Config[]
     */
    public function provide(array $data): array
    {
        return [
            new Config(
                'system/general/hide_powered_by',
                '0',
                'Hide "Powered by SolidInvoice" text in invoices and quotes.',
                CheckboxType::class,
                ['feature_gated' => Feature::CustomBranding->value]
            ),
            new Config(
                'system/domain/custom_domain',
                null,
                'Custom domain for this company (leave empty to use the default URL).',
                CustomDomainType::class,
                [
                    'feature_gated' => Feature::CustomDomain->value,
                    'trial_restricted' => true,
                ],
            ),
        ];
    }
}
