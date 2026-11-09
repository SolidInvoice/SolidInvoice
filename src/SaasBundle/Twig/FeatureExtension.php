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

use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SaasBundle\Feature\FeatureCopy;
use SolidInvoice\SaasBundle\Feature\FeatureCopyRegistry;
use SolidInvoice\SaasBundle\Feature\UpgradePromptRenderer;
use SolidInvoice\SaasBundle\Service\SubscriptionService;
use SolidInvoice\SaasBundle\Trial\TrialCta;
use SolidInvoice\SaasBundle\Trial\TrialOffer;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureType;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Attribute\AsTwigFunction;
use function array_filter;
use function is_array;
use function parse_url;

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
        private TrialOffer $trialOffer,
        private SubscriptionService $subscriptionService,
        private Security $security,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * The trial a gate can offer for this feature: start one, or move a running
     * one up. Null means the gate keeps its "Upgrade" action.
     *
     * Gates render only once the user is blocked. For a limit (clients,
     * invoices a month, seats) "enabled" only means the limit is not zero, so
     * Free's limit of 3 still counts as enabled: only an on/off feature that is
     * already on is skipped.
     *
     * @return array{plan: string, days: int|null, moving_up: bool, url: string}|null
     */
    #[AsTwigFunction(name: 'trial_offer')]
    public function trialOffer(string $featureKey): ?array
    {
        $user = $this->security->getUser();
        $subscription = $this->subscriptionService->current();

        if (! $user instanceof User || ! $subscription instanceof Subscription) {
            return null;
        }

        if (Feature::tryFrom($featureKey)?->getType() === FeatureType::BOOLEAN && $this->gate->isEnabled($featureKey)) {
            return null;
        }

        $plan = $this->trialOffer->planForFeature($featureKey, $subscription);
        $offer = $plan instanceof Plan ? $this->trialOffer->offer($plan, $user, $subscription) : null;

        if (! $offer instanceof TrialCta) {
            return null;
        }

        return [
            'plan' => $offer->plan->getName(),
            'days' => $offer->days,
            'moving_up' => $offer->movingUp,
            'url' => $this->urlGenerator->generate('saas_trial_start', array_filter([
                'feature' => $featureKey,
                'return' => $this->returnPath(),
            ])),
        ];
    }

    /**
     * The page the user is on. A live component re-renders through its own
     * endpoint, so the page is the one that sent the request.
     */
    private function returnPath(): ?string
    {
        $request = $this->requestStack->getMainRequest();

        if (! $request instanceof Request) {
            return null;
        }

        if ($request->attributes->get('_route') !== 'ux_live_component') {
            return $request->getRequestUri();
        }

        $referer = parse_url((string) $request->headers->get('referer'));

        if (! is_array($referer) || ! isset($referer['path'])) {
            return null;
        }

        return $referer['path'] . (isset($referer['query']) ? '?' . $referer['query'] : '');
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
