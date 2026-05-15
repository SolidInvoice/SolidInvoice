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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use Override;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Self-hosted no-op for the SaaS-only Twig functions exposed by
 * SolidInvoice\SaasBundle\Twig\FeatureExtension.
 *
 * Vendor PlatformBundle already exposes `feature_enabled`, `feature_can_use`
 * and `feature_remaining` in every deployment, so we only stub the three
 * names that are SaaS-specific. This extension is registered conditionally
 * (only when SOLIDINVOICE_PLATFORM is not 'saas') so SaaS deployments can
 * register the real implementations without colliding on Twig function names.
 */
final class FeatureExtension extends AbstractExtension
{
    /**
     * @return list<TwigFunction>
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'feature_required_plan_label',
                static fn (string $featureKey): ?string => null,
            ),
            new TwigFunction(
                'upgrade_prompt',
                static fn (string $featureKey): string => '',
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'feature_usage_banner',
                static fn (string $featureKey, int $currentUsage = 0): string => '',
                ['is_safe' => ['html']],
            ),
            new TwigFunction(
                'feature_copy',
                static fn (string $featureKey): ?object => null,
            ),
        ];
    }
}
