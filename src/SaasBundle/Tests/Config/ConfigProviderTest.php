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

namespace SolidInvoice\SaasBundle\Tests\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Config\ConfigProvider;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\DTO\Config;

#[CoversClass(ConfigProvider::class)]
final class ConfigProviderTest extends TestCase
{
    public function testHidePoweredByIsGatedByCustomBrandingFeature(): void
    {
        $configs = (new ConfigProvider())->provide([]);

        $hidePoweredBy = $this->findConfigByKey($configs, 'system/general/hide_powered_by');

        self::assertNotNull($hidePoweredBy, 'hide_powered_by config not registered');
        self::assertSame(Feature::CustomBranding->value, $hidePoweredBy->formOptions['feature_gated'] ?? null);
        self::assertArrayNotHasKey(
            'trial_restricted',
            $hidePoweredBy->formOptions,
            'hide_powered_by should be feature-gated, not trial-restricted',
        );
    }

    public function testCustomDomainRemainsTrialRestricted(): void
    {
        $configs = (new ConfigProvider())->provide([]);

        $customDomain = $this->findConfigByKey($configs, 'system/company/custom_domain');

        self::assertNotNull($customDomain);
        self::assertTrue($customDomain->formOptions['trial_restricted'] ?? false);
    }

    /**
     * @param list<Config> $configs
     */
    private function findConfigByKey(array $configs, string $key): ?Config
    {
        foreach ($configs as $config) {
            if ($config->key === $key) {
                return $config;
            }
        }

        return null;
    }
}
