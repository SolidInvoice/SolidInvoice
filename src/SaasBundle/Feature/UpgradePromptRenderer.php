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

namespace SolidInvoice\SaasBundle\Feature;

use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Environment;

/**
 * Builds upgrade-prompt UI fragments by combining `FeatureGate::upgradeOptions()`
 * with persisted `Plan` entities (for prices) and the shared banner partial.
 *
 * Lives in SolidInvoice's SaasBundle (not in vendor PlatformBundle) because the
 * banner copy and route names (`saas_subscription_plans`) are SolidInvoice-specific.
 * @see \SolidInvoice\SaasBundle\Tests\Feature\UpgradePromptRendererTest
 */
final readonly class UpgradePromptRenderer implements RequiredPlanLabelProvider, UpgradePromptProvider
{
    private const string BANNER_TEMPLATE = '@SolidInvoiceSaas/feature/_upgrade_banner.html.twig';

    public function __construct(
        private FeatureGate $gate,
        private PlanRepositoryInterface $planRepository,
        private Environment $twig,
        private TranslatorInterface $translator,
        private FeatureUsage $usage,
    ) {
    }

    /**
     * Returns the cheapest paid Plan that exposes the feature, or null when no
     * paid upgrade path exists.
     *
     * The Free plan is intentionally excluded: every signed-in user is already
     * on at least Free (the floor), so surfacing "Upgrade to Free" as a nudge
     * is confusing — and for features that ship in Free, the gate should not
     * fire for an authenticated user in the first place.
     */
    public function lowestPlanFor(string $featureKey): ?Plan
    {
        $options = $this->gate->upgradeOptions($featureKey);

        if ($options->isEmpty()) {
            return null;
        }

        $cheapest = null;

        foreach ($options->plans as $reference) {
            $plan = $this->resolvePlan($reference->id);

            if (! $plan instanceof Plan) {
                continue;
            }

            // Free is the floor — it isn't a meaningful upgrade target.
            if ($plan->isFree()) {
                continue;
            }

            if (! $cheapest instanceof Plan || $plan->getPrice() < $cheapest->getPrice()) {
                $cheapest = $plan;
            }
        }

        return $cheapest;
    }

    /**
     * Renders an upgrade prompt banner. Returns an empty string when no upgrade
     * path exists (self-hosted, all-plans-have-it, or unknown plan reference).
     */
    public function prompt(string $featureKey): string
    {
        $plan = $this->lowestPlanFor($featureKey);

        if (! $plan instanceof Plan) {
            return '';
        }

        return $this->twig->render(self::BANNER_TEMPLATE, [
            'title' => $this->translator->trans('Upgrade required'),
            'message' => $this->translator->trans(
                'This feature is available on the %plan% plan.',
                ['%plan%' => $plan->getName()],
            ),
            'plan_name' => $plan->getName(),
            'type' => 'warning',
            'icon' => 'tabler:arrow-up-circle',
        ]);
    }

    /**
     * Returns a short menu/link suffix label like "Solo plan", or null when no
     * upgrade path is available. Useful for greying-out menu items with a hint.
     */
    public function menuLabel(string $featureKey): ?string
    {
        $plan = $this->lowestPlanFor($featureKey);

        return $plan instanceof Plan ? $plan->getName() : null;
    }

    /**
     * Renders a usage-warning banner when the subject is approaching the quota.
     * Returns an empty string for unlimited features, off-quota features, or
     * when remaining is comfortably above the 10% threshold.
     */
    public function usageBanner(string $featureKey, int $currentUsage = 0): string
    {
        $value = $this->gate->resolve($featureKey);

        if ($value->isUnlimited()) {
            return '';
        }

        $remaining = $this->gate->remaining($featureKey, $currentUsage);
        $total = $value->asInt();

        if ($remaining === null || ! $this->usage->isApproachingLimit($remaining, $total)) {
            return '';
        }

        $plan = $this->lowestPlanFor($featureKey);

        return $this->twig->render(self::BANNER_TEMPLATE, [
            'title' => $this->translator->trans('Approaching plan limit'),
            'message' => $this->translator->trans(
                '%remaining% of %total% remaining.',
                ['%remaining%' => $remaining, '%total%' => $total],
            ),
            'plan_name' => $plan instanceof Plan ? $plan->getName() : null,
            'type' => 'warning',
            'icon' => 'tabler:alert-triangle',
        ]);
    }

    private function resolvePlan(string $base58Id): ?Plan
    {
        try {
            $ulid = Ulid::fromBase58($base58Id);
        } catch (Throwable) {
            return null;
        }

        return $this->planRepository->find($ulid);
    }
}
