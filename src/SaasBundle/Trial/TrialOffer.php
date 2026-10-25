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

namespace SolidInvoice\SaasBundle\Trial;

use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SaasBundle\Plan\TrialPeriod;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Entity\Trial;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Feature\PlanFeatureManager;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Trial\TrialManagerInterface;

/**
 * The opt-in trial follows the feature the user reached for: it runs on the
 * cheapest plan that has that feature and a trial length. A user gets one trial
 * ever; while it runs, the company can move it up to a dearer plan without
 * moving its end date.
 *
 * Nothing here is hard-coded: plan names, prices and trial lengths are the
 * plans' own rows, so a plan with no trial duration is never offered.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Trial\TrialOfferTest
 */
final readonly class TrialOffer
{
    public function __construct(
        private TrialManagerInterface $trialManager,
        private PlanFeatureManager $planFeatureManager,
        private PlanRepositoryInterface $planRepository,
        private TrialPeriod $trialPeriod,
        private ClockInterface $clock,
        private ManagerRegistry $registry,
        private BillingMode $billingMode,
    ) {
    }

    /**
     * A user who has never had a trial can start one, on any company.
     */
    public function isEligible(User $user): bool
    {
        return ! $this->trialManager->userHasTrial($user);
    }

    public function isRunning(Subscription $subscription): bool
    {
        return $subscription->getStatus() === SubscriptionStatus::TRIAL
            && $subscription->getEndDate() > $this->clock->now();
    }

    /**
     * The cheapest plan that unlocks the feature for this subscription. Above
     * its current plan, so a limit gate on a trial plan finds the next tier up.
     */
    public function planForFeature(string $featureKey, Subscription $subscription): ?Plan
    {
        if (Feature::tryFrom($featureKey)?->isTrialable() !== true) {
            return null;
        }

        $cheapest = null;

        foreach ($this->planFeatureManager->findPlansWithFeature($featureKey) as $plan) {
            if ($this->isOffered($plan, $subscription) && (! $cheapest instanceof Plan || $plan->getPrice() < $cheapest->getPrice())) {
                $cheapest = $plan;
            }
        }

        return $cheapest;
    }

    /**
     * A plan asked for by name (a pricing page link), if it can be trialled.
     */
    public function planById(string $planId, Subscription $subscription): ?Plan
    {
        $plan = $this->planRepository->find($planId);

        return $plan instanceof Plan && $this->isOffered($plan, $subscription) ? $plan : null;
    }

    /**
     * The trial this user can take on this plan right now: a running trial on
     * the company can only move up (restarting it would reset its end date);
     * otherwise the user needs their one trial unused, and a billed subscription
     * changes plan through the payment provider, never here. The trial table
     * holds one row per subscription too, so a company another member already
     * trialled cannot start a second one.
     */
    public function offer(Plan $plan, User $user, Subscription $subscription): ?TrialCta
    {
        // Checked first: a trial with a card on file is billed by the payment
        // provider, and changePlan() would only move it locally. In paid-trial
        // mode every trial starts at checkout with a card, never here.
        if ($subscription->isExternallyBilled() || $this->billingMode->requiresCardForTrial()) {
            return null;
        }

        if ($this->isRunning($subscription)) {
            return new TrialCta($plan, $this->trialPeriod->days($plan), movingUp: true);
        }

        if (! $this->isEligible($user) || $this->hadTrial($subscription)) {
            return null;
        }

        return new TrialCta($plan, $this->trialPeriod->days($plan), movingUp: false);
    }

    private function hadTrial(Subscription $subscription): bool
    {
        return $this->registry->getRepository(Trial::class)->findOneBy(['subscription' => $subscription]) instanceof Trial;
    }

    private function isOffered(Plan $plan, Subscription $subscription): bool
    {
        return $plan->isActive()
            && ! $plan->isFree()
            && $this->trialPeriod->hasTrial($plan)
            && $plan->getPrice() > $subscription->getPlan()->getPrice();
    }
}
