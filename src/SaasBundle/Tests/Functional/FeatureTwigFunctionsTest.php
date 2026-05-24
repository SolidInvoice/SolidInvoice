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
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureGate;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * SaaS (PlanFeatureGate) wiring contract for the six feature Twig helpers.
 *
 * With no subscriber attached, PlanFeatureGate falls back to the
 * `solidworx_platform.saas.features` config defaults. INTEGER quotas default
 * to {@see \SolidWorx\Platform\PlatformBundle\Feature\FeatureValue::UNLIMITED}
 * (-1) and BOOLEAN flags default to true, so the helpers behave like the
 * NoopFeatureGate in this baseline state — but the gate concrete is the
 * real PlanFeatureGate, proving the wiring took effect.
 */
#[Group('functional')]
final class FeatureTwigFunctionsTest extends KernelTestCase
{
    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    protected static function createKernel(array $options = []): SaasTestKernel
    {
        $env = $options['environment'] ?? $_ENV['SOLIDINVOICE_ENV'] ?? $_SERVER['SOLIDINVOICE_ENV'] ?? 'test';
        $debugRaw = $options['debug'] ?? $_ENV['SOLIDINVOICE_DEBUG'] ?? $_SERVER['SOLIDINVOICE_DEBUG'] ?? true;
        $debug = is_bool($debugRaw)
            ? $debugRaw
            : filter_var((string) $debugRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        return new SaasTestKernel($env, $debug);
    }

    public function testFeatureGateAliasResolvesToPlanFeatureGateInSaasMode(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $gateId = 'test.' . FeatureGate::class;
        self::assertTrue($container->has($gateId));
        self::assertInstanceOf(PlanFeatureGate::class, $container->get($gateId));
    }

    public function testFeatureEnabledHonoursConfigDefaultsWhenNoPlanAttached(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        // BOOLEAN feature defaults to true → enabled.
        $template = $twig->createTemplate(
            "{{ feature_enabled('" . Feature::Quotes->value . "') ? 'yes' : 'no' }}"
        );
        self::assertSame('yes', $template->render());

        // INTEGER feature defaults to -1 (unlimited) → also enabled.
        $template = $twig->createTemplate(
            "{{ feature_enabled('" . Feature::TotalClients->value . "') ? 'yes' : 'no' }}"
        );
        self::assertSame('yes', $template->render());
    }

    public function testUpgradePromptIsEmptyWhenFeatureIsEnabled(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        // Default config has every feature enabled, so upgrade_prompt is empty.
        $template = $twig->createTemplate(
            "{{ upgrade_prompt('" . Feature::Quotes->value . "') }}"
        );
        self::assertSame('', $template->render());
    }

    public function testUsageBannerIsEmptyForUnlimitedDefaultQuota(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        // TotalClients defaults to -1 (unlimited) → no banner regardless of usage.
        $template = $twig->createTemplate(
            "{{ feature_usage_banner('" . Feature::TotalClients->value . "', 1000) }}"
        );
        self::assertSame('', $template->render());
    }

    public function testRequiredPlanLabelIsNullWhenFeatureIsEnabled(): void
    {
        self::bootKernel();

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        // No plan is attached and defaults enable everything → no required plan.
        $template = $twig->createTemplate(
            "{% set label = feature_required_plan_label('" . Feature::McpAccess->value . "') %}{{ label is null ? 'null' : label }}"
        );
        self::assertSame('null', $template->render());
    }
}
