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
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Telemetry\TelemetryEvent;
use SolidWorx\Platform\SaasBundle\Dto\LemonSqueezy\Subscription as LemonSqueezySubscription;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionCreatedEvent;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionEvent;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionUpdatedEvent;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use function strtolower;

/**
 * Listens to Lemon Squeezy `subscription_created` / `subscription_updated`
 * webhook events and synchronises the local subscription's plan with the
 * variant id reported by Lemon Squeezy.
 *
 * This is the *only* place a paid-plan switch commits to the database. The
 * choose-plan and confirm-plan-change actions defer the local mutation
 * entirely so that an LS error (e.g. checkout failure, mis-configured
 * variant) cannot leave the app on a plan the user never actually paid for.
 *
 * Webhooks arrive as separate HTTP requests with no user session attached,
 * so the listener is intentionally stateless — it relies purely on the LS
 * payload's `variantId` and the persisted local subscription.
 *
 * Free plans are handled inline by ChoosePlanAction / ConfirmPlanChangeAction
 * — they never round-trip through LS — and active-billed plan changes go
 * through `SubscriptionManager::changeActivePlan()` which is already
 * LS-confirmed before the local update.
 * @see \SolidInvoice\SaasBundle\Tests\EventSubscriber\SubscriptionPlanSyncListenerTest
 */
final readonly class SubscriptionPlanSyncListener
{
    public function __construct(
        private SubscriptionRepositoryInterface $subscriptionRepository,
        private PlanRepositoryInterface $planRepository,
        private LoggerInterface $logger,
        private Telemetry $telemetry,
    ) {
    }

    #[AsEventListener(event: SubscriptionCreatedEvent::class)]
    public function onSubscriptionCreated(SubscriptionCreatedEvent $event): void
    {
        $this->sync($event);
    }

    #[AsEventListener(event: SubscriptionUpdatedEvent::class)]
    public function onSubscriptionUpdated(SubscriptionUpdatedEvent $event): void
    {
        $this->sync($event);
    }

    private function sync(SubscriptionEvent $event): void
    {
        $remote = $event->subscription;

        if (! $remote instanceof LemonSqueezySubscription) {
            // Non-LS DTO: nothing to compare against. Other payment providers
            // can add their own listeners following this same shape.
            return;
        }

        $subscription = $this->subscriptionRepository->findOneBy(['id' => $event->subscriptionId]);

        if (! $subscription instanceof Subscription) {
            return;
        }

        $remoteVariantId = (string) $remote->attributes->variantId;
        $currentPlanId = $subscription->getPlan()->getPlanId();

        if ($remoteVariantId === $currentPlanId) {
            // Plan unchanged — nothing to sync.
            return;
        }

        $targetPlan = $this->planRepository->find($remoteVariantId);

        if (! $targetPlan instanceof Plan) {
            $this->logger->warning(
                'Received subscription webhook with unknown variant id; local plan unchanged.',
                [
                    'subscription_id' => $event->subscriptionId->toBase58(),
                    'variant_id' => $remoteVariantId,
                ],
            );

            return;
        }

        // Capture whether this is a first conversion (free → paid) before the
        // plan is overwritten, so the telemetry below counts genuine
        // activations and not paid → paid upgrades (e.g. solo → business).
        $isFirstConversion = $subscription->getPlan()->isFree() && ! $targetPlan->isFree();

        // Skip SubscriptionManager::changePlan() here: it guards against
        // mutating ACTIVE externally-billed subscriptions, which is *exactly*
        // the state this listener fires for. Lemon Squeezy is the authority
        // for the switch — we just record it.
        $subscription->setPlan($targetPlan);
        $this->subscriptionRepository->save($subscription);

        if ($isFirstConversion) {
            $this->telemetry->event(TelemetryEvent::SaasSubscriptionActivated, [
                'plan' => strtolower($targetPlan->getName()),
            ]);
        }
    }
}
