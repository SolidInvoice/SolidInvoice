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

namespace SolidInvoice\SaasBundle\Tests\EventSubscriber;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SolidInvoice\SaasBundle\EventSubscriber\RetiredPendingPlanListener;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionExpiredEvent;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use Symfony\Component\Uid\Ulid;

#[CoversClass(RetiredPendingPlanListener::class)]
final class RetiredPendingPlanListenerTest extends TestCase
{
    /**
     * A downgrade scheduled before the target plan was retired would otherwise
     * be applied on expiry, silently reopening a closed tier for that tenant.
     */
    public function testAPendingChangeToARetiredPlanIsDiscarded(): void
    {
        $subscription = $this->subscriptionPendingChangeTo($this->plan(active: false));

        $this->listenerFor($subscription)->onSubscriptionExpired($this->event());

        self::assertNull($subscription->getPendingPlan());
        self::assertNull($subscription->getPendingPlanChangeAt());
        self::assertFalse($subscription->hasPendingPlanChange());
    }

    public function testAPendingChangeToAnActivePlanIsLeftAlone(): void
    {
        $activePlan = $this->plan(active: true);
        $subscription = $this->subscriptionPendingChangeTo($activePlan);

        $this->listenerFor($subscription)->onSubscriptionExpired($this->event());

        self::assertSame($activePlan, $subscription->getPendingPlan());
        self::assertTrue($subscription->hasPendingPlanChange());
    }

    public function testASubscriptionWithNoPendingChangeIsUntouched(): void
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan(active: true));

        $this->listenerFor($subscription)->onSubscriptionExpired($this->event());

        self::assertFalse($subscription->hasPendingPlanChange());
    }

    public function testAnUnknownSubscriptionIsIgnored(): void
    {
        $this->listenerFor(null)->onSubscriptionExpired($this->event());

        $this->expectNotToPerformAssertions();
    }

    private function listenerFor(?Subscription $subscription): RetiredPendingPlanListener
    {
        $repository = $this->createMock(SubscriptionRepositoryInterface::class);
        $repository->method('findOneBy')->willReturn($subscription);

        return new RetiredPendingPlanListener($repository, new NullLogger());
    }

    private function subscriptionPendingChangeTo(Plan $pendingPlan): Subscription
    {
        $subscription = new Subscription();
        $subscription->setPlan($this->plan(active: true));
        $subscription->setPendingPlan($pendingPlan);
        $subscription->setPendingPlanChangeAt(CarbonImmutable::parse('2024-02-01'));

        return $subscription;
    }

    private function plan(bool $active): Plan
    {
        $plan = new Plan();
        $plan->setName('Free');
        $plan->setPlanId('0');
        $plan->setPrice(0);
        $plan->setActive($active);

        return $plan;
    }

    private function event(): SubscriptionExpiredEvent
    {
        return new SubscriptionExpiredEvent(new Ulid(), 'ls_sub_1', null);
    }
}
