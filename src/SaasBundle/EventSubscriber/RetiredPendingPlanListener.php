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

namespace SolidInvoice\SaasBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionExpiredEvent;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Drops a scheduled plan change whose target plan has since been retired.
 *
 * A downgrade is stored as a `pendingPlan` FK and only applied when the
 * provider reports the subscription expired at the end of the paid period.
 * `SubscriptionManager::applyScheduledPlanChange()` does not check whether the
 * target plan is still active, so a downgrade scheduled *before* a plan was
 * retired would still land on it — silently reopening a closed tier for that
 * tenant, with a 100-year end date and no external subscription id.
 *
 * Clearing the pending change here means the vendor listener falls through to
 * expiring the subscription instead, which puts the tenant in front of the
 * plan picker rather than on a plan that is no longer sold.
 *
 * Runs before SubscriptionEventSubscriber::onSubscriptionExpired (priority 0).
 *
 * @see \SolidInvoice\SaasBundle\Tests\EventSubscriber\RetiredPendingPlanListenerTest
 */
final readonly class RetiredPendingPlanListener
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener(event: SubscriptionExpiredEvent::class, priority: 10)]
    public function onSubscriptionExpired(SubscriptionExpiredEvent $event): void
    {
        $subscription = $this->subscriptionRepository->findOneBy(['id' => $event->subscriptionId]);

        if (! $subscription instanceof Subscription || ! $subscription->hasPendingPlanChange()) {
            return;
        }

        $pendingPlan = $subscription->getPendingPlan();

        if (! $pendingPlan instanceof Plan || $pendingPlan->isActive()) {
            return;
        }

        $this->logger->warning(
            'Discarding scheduled plan change: the target plan is no longer active.',
            [
                'subscription_id' => $event->subscriptionId->toBase58(),
                'pending_plan' => $pendingPlan->getPlanId(),
            ],
        );

        $subscription->setPendingPlan(null);
        $subscription->setPendingPlanChangeAt(null);

        $this->subscriptionRepository->save($subscription);
    }
}
