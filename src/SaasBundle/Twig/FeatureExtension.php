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

namespace SolidInvoice\SaasBundle\Twig;

use SolidInvoice\SaasBundle\Feature\FeatureCopy;
use SolidInvoice\SaasBundle\Feature\FeatureCopyRegistry;
use SolidInvoice\SaasBundle\Feature\UpgradePromptRenderer;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use Twig\Attribute\AsTwigFunction;

/**
 * Twig functions for SaaS-only upgrade UI: required-plan label, locked-feature
 * prompt, and the approaching-limit usage banner.
 *
 * The three "read-only" feature questions (`feature_enabled`, `feature_can_use`,
 * `feature_remaining`) are already exposed by `SolidWorx\Platform\PlatformBundle\Twig\Extension\FeatureExtension`,
 * which is loaded in every deployment. We deliberately do *not* re-register
 * those three names here to avoid Twig "function already defined" errors;
 * SaaS deployments inherit them from the platform extension and they resolve
 * via the gate alias (NoopFeatureGate / PlanFeatureGate) automatically.
 *
 * In self-hosted deployments where SaasBundle is not loaded, the no-op
 * SolidInvoice\CoreBundle\Twig\Extension\FeatureExtension provides the three
 * SaaS-only names so templates can call them unconditionally.
 */
final readonly class FeatureExtension
{
    public function __construct(
        private UpgradePromptRenderer $renderer,
        private FeatureGate $gate,
        private FeatureCopyRegistry $copyRegistry,
    ) {
    }

    #[AsTwigFunction(name: 'feature_copy')]
    public function featureCopy(string $featureKey): ?FeatureCopy
    {
        return $this->copyRegistry->get($featureKey);
    }

    #[AsTwigFunction(name: 'feature_required_plan_label')]
    public function requiredPlanLabel(string $featureKey): ?string
    {
        if ($this->gate->isEnabled($featureKey)) {
            return null;
        }

        return $this->renderer->menuLabel($featureKey);
    }

    #[AsTwigFunction(name: 'upgrade_prompt', isSafe: ['html'])]
    public function upgradePrompt(string $featureKey): string
    {
        if ($this->gate->isEnabled($featureKey)) {
            return '';
        }

        return $this->renderer->prompt($featureKey);
    }

    #[AsTwigFunction(name: 'feature_usage_banner', isSafe: ['html'])]
    public function usageBanner(string $featureKey, int $currentUsage = 0): string
    {
        return $this->renderer->usageBanner($featureKey, $currentUsage);
    }
}
