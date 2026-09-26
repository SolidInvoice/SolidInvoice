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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Self-hosted (NoopFeatureGate) wiring contract for the six feature Twig
 * helpers. The three "read" functions come from vendor PlatformBundle's
 * FeatureExtension; the three "upgrade UI" functions come from CoreBundle's
 * no-op FeatureExtension (which is conditionally registered when
 * SOLIDINVOICE_PLATFORM is not 'saas').
 */
final class FeatureTwigFunctionsTest extends KernelTestCase
{
    public function testFeatureGateResolvesToNoopGateInSelfHostedMode(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $gateId = 'test.' . FeatureGate::class;
        self::assertTrue($container->has($gateId));
        self::assertInstanceOf(NoopFeatureGate::class, $container->get($gateId));
    }

    public function testReadOnlyFeatureFunctionsReturnUnlimitedDefaults(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        // Vendor PlatformBundle wiring: every feature is treated as enabled
        // and unlimited because NoopFeatureGate has no opinion on keys.
        self::assertSame('yes', $twig->createTemplate("{{ feature_enabled('any_key') ? 'yes' : 'no' }}")->render());
        self::assertSame('yes', $twig->createTemplate("{{ feature_can_use('any_key', 9999) ? 'yes' : 'no' }}")->render());
        self::assertSame('', $twig->createTemplate("{{ feature_remaining('any_key') }}")->render());
    }

    public function testSaasOnlyFunctionsResolveToNoopValues(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        self::assertSame('', $twig->createTemplate("{{ feature_required_plan_label('any_key') }}")->render());
        self::assertSame('', $twig->createTemplate("{{ upgrade_prompt('any_key') }}")->render());
        self::assertSame('', $twig->createTemplate("{{ feature_usage_banner('any_key', 5) }}")->render());
    }
}
