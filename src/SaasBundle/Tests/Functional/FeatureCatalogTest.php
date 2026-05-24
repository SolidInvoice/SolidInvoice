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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use Override;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SaasBundle\Tests\SaasTestKernel;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\SaasBundle\Feature\FeatureConfigRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Asserts that the {@see Feature} enum and the configured feature catalogue
 * (`solidworx_platform.saas.features`, processed into FeatureConfigRegistry)
 * stay in lock-step.
 *
 * Drift between the enum and the config is the most likely silent failure
 * here — adding a Feature case without registering it (or vice versa) would
 * leak through unit tests because the registry is never consulted there.
 */
#[Group('functional')]
final class FeatureCatalogTest extends KernelTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    protected static function createKernel(array $options = []): SaasTestKernel
    {
        $env = $options['environment'] ?? $_ENV['SOLIDINVOICE_ENV'] ?? $_SERVER['SOLIDINVOICE_ENV'] ?? 'test';
        $debug = $options['debug'] ?? (bool) ($_ENV['SOLIDINVOICE_DEBUG'] ?? $_SERVER['SOLIDINVOICE_DEBUG'] ?? true);

        return new SaasTestKernel($env, $debug);
    }

    public function testEveryFeatureEnumCaseIsRegisteredInTheRegistry(): void
    {
        $registry = $this->getRegistry();

        foreach (Feature::cases() as $feature) {
            self::assertTrue(
                $registry->has($feature->value),
                sprintf('Feature "%s" is not registered in FeatureConfigRegistry — update platform.yaml saas.features.', $feature->value),
            );

            $config = $registry->get($feature->value);
            self::assertSame(
                $feature->getType(),
                $config->type,
                sprintf('Feature "%s" type drift: enum says %s, config says %s.', $feature->value, $feature->getType()->value, $config->type->value),
            );
        }
    }

    public function testRegistryDoesNotContainUnknownFeatures(): void
    {
        $registry = $this->getRegistry();

        $known = array_map(static fn (Feature $f): string => $f->value, Feature::cases());

        foreach ($registry->keys() as $registeredKey) {
            self::assertContains(
                $registeredKey,
                $known,
                sprintf('Registry contains feature "%s" with no matching Feature enum case.', $registeredKey),
            );
        }
    }

    public function testFeatureGateResolveReturnsConfiguredDefaultWithNoPlanAttached(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $gateId = 'test.' . FeatureGate::class;
        self::assertTrue($container->has($gateId));
        $gate = $container->get($gateId);
        self::assertInstanceOf(FeatureGate::class, $gate);

        $registry = $this->getRegistry();

        foreach (Feature::cases() as $feature) {
            $expectedDefault = $registry->get($feature->value)->defaultValue;

            $resolved = $gate->resolve($feature->value);

            self::assertSame(
                $expectedDefault,
                $resolved->value,
                sprintf('FeatureGate did not return the configured default for "%s" with no plan attached.', $feature->value),
            );
        }
    }

    private function getRegistry(): FeatureConfigRegistry
    {
        self::bootKernel();

        $container = self::getContainer();

        $id = 'test.' . FeatureConfigRegistry::class;
        self::assertTrue(
            $container->has($id),
            sprintf('Test alias "%s" is not registered. Ensure SOLIDINVOICE_PLATFORM=saas and config/services_test.php is loaded.', $id),
        );

        $registry = $container->get($id);
        self::assertInstanceOf(FeatureConfigRegistry::class, $registry);

        return $registry;
    }
}
