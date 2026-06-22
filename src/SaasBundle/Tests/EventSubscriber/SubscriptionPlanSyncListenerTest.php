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

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ReflectionProperty;
use SolidInvoice\CoreBundle\ConfigWriter;
use SolidInvoice\CoreBundle\Telemetry\Telemetry;
use SolidInvoice\CoreBundle\Tests\Telemetry\CollectingMessageBus;
use SolidInvoice\SaasBundle\EventSubscriber\SubscriptionPlanSyncListener;
use SolidWorx\Platform\SaasBundle\Dto\LemonSqueezy\Subscription as LemonSqueezySubscription;
use SolidWorx\Platform\SaasBundle\Dto\LemonSqueezy\SubscriptionAttributes;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionCreatedEvent;
use SolidWorx\Platform\SaasBundle\Event\SubscriptionUpdatedEvent;
use SolidWorx\Platform\SaasBundle\Repository\PlanRepositoryInterface;
use SolidWorx\Platform\SaasBundle\Repository\SubscriptionRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Secrets\AbstractVault;
use Symfony\Component\Uid\Ulid;

#[CoversClass(SubscriptionPlanSyncListener::class)]
final class SubscriptionPlanSyncListenerTest extends TestCase
{
    private CollectingMessageBus $bus;

    protected function setUp(): void
    {
        $this->bus = new CollectingMessageBus();
    }

    public function testCommitsPlanChangeWhenVariantIdDiffersOnCreation(): void
    {
        $currentPlan = $this->makePlan('Free', '0');
        $newPlan = $this->makePlan('Solo', '12345');
        $subscription = $this->makeSubscription($currentPlan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::atLeastOnce())->method('findOneBy')->willReturn($subscription);
        $subscriptionRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn (Subscription $s): bool => $s->getPlan() === $newPlan));

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->expects(self::once())->method('find')->with('12345')->willReturn($newPlan);

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        $listener->onSubscriptionCreated($this->makeCreatedEvent($subscription->getId(), 12345));

        self::assertSame($newPlan, $subscription->getPlan());
    }

    public function testCommitsPlanChangeWhenVariantIdDiffersOnUpdate(): void
    {
        $currentPlan = $this->makePlan('Solo', '12345');
        $newPlan = $this->makePlan('Business', '67890');
        $subscription = $this->makeSubscription($currentPlan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::atLeastOnce())->method('findOneBy')->willReturn($subscription);
        $subscriptionRepository->expects(self::once())->method('save');

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->expects(self::once())->method('find')->with('67890')->willReturn($newPlan);

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        $listener->onSubscriptionUpdated($this->makeUpdatedEvent($subscription->getId(), 67890));

        self::assertSame($newPlan, $subscription->getPlan());
    }

    public function testDoesNotChangePlanWhenVariantIdMatchesCurrentPlan(): void
    {
        $currentPlan = $this->makePlan('Solo', '12345');
        $subscription = $this->makeSubscription($currentPlan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::atLeastOnce())->method('findOneBy')->willReturn($subscription);
        $subscriptionRepository->expects(self::never())->method('save');

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->expects(self::never())->method('find');

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        $listener->onSubscriptionUpdated($this->makeUpdatedEvent($subscription->getId(), 12345));

        self::assertSame($currentPlan, $subscription->getPlan());
    }

    public function testIgnoresEventWithoutLemonSqueezyDto(): void
    {
        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::never())->method('findOneBy');
        $subscriptionRepository->expects(self::never())->method('save');

        $planRepository = $this->createStub(PlanRepositoryInterface::class);

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        // No DTO attached — listener should short-circuit.
        $listener->onSubscriptionUpdated(new SubscriptionUpdatedEvent(new Ulid(), 'lemon-1234', null));
    }

    public function testSkipsChangePlanWhenVariantIdUnknown(): void
    {
        $currentPlan = $this->makePlan('Free', '0');
        $subscription = $this->makeSubscription($currentPlan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::atLeastOnce())->method('findOneBy')->willReturn($subscription);
        $subscriptionRepository->expects(self::never())->method('save');

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->expects(self::once())->method('find')->with('99999')->willReturn(null);

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        $listener->onSubscriptionUpdated($this->makeUpdatedEvent($subscription->getId(), 99999));

        self::assertSame($currentPlan, $subscription->getPlan());
    }

    public function testIgnoresEventWhenLocalSubscriptionMissing(): void
    {
        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->expects(self::atLeastOnce())->method('findOneBy')->willReturn(null);
        $subscriptionRepository->expects(self::never())->method('save');

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->expects(self::never())->method('find');

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        $listener->onSubscriptionUpdated($this->makeUpdatedEvent(new Ulid(), 12345));
    }

    public function testEmitsActivationTelemetryOnFreeToPaidConversion(): void
    {
        $currentPlan = $this->makePlan('Free', '0');
        $newPlan = $this->makePlan('Solo', '12345');
        $subscription = $this->makeSubscription($currentPlan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->method('findOneBy')->willReturn($subscription);

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->expects(self::once())->method('find')->with('12345')->willReturn($newPlan);

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        $listener->onSubscriptionCreated($this->makeCreatedEvent($subscription->getId(), 12345));

        self::assertCount(1, $this->bus->messages);
        self::assertSame('event', $this->bus->messages[0]->type);
        self::assertSame('saas_subscription_activated', $this->bus->messages[0]->payload['event']);
        self::assertSame('solo', $this->bus->messages[0]->payload['properties']['plan']);
    }

    public function testDoesNotEmitActivationTelemetryOnPaidToPaidUpgrade(): void
    {
        $currentPlan = $this->makePlan('Solo', '12345');
        $newPlan = $this->makePlan('Business', '67890');
        $subscription = $this->makeSubscription($currentPlan);

        $subscriptionRepository = $this->createMock(SubscriptionRepositoryInterface::class);
        $subscriptionRepository->method('findOneBy')->willReturn($subscription);

        $planRepository = $this->createMock(PlanRepositoryInterface::class);
        $planRepository->expects(self::once())->method('find')->with('67890')->willReturn($newPlan);

        $listener = $this->makeListener($subscriptionRepository, $planRepository);

        $listener->onSubscriptionUpdated($this->makeUpdatedEvent($subscription->getId(), 67890));

        self::assertSame([], $this->bus->messages);
    }

    private function makeListener(
        SubscriptionRepositoryInterface $subscriptionRepository,
        PlanRepositoryInterface $planRepository,
    ): SubscriptionPlanSyncListener {
        return new SubscriptionPlanSyncListener(
            $subscriptionRepository,
            $planRepository,
            new NullLogger(),
            $this->makeTelemetry(),
        );
    }

    private function makeTelemetry(): Telemetry
    {
        $vault = $this->createMock(AbstractVault::class);
        $vault->method('generateKeys')->willReturn(true);

        return new Telemetry(
            $this->bus,
            new ConfigWriter($vault, '/tmp/solidinvoice-test-config'),
            DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]),
            'build-123',
            true,
            'manual',
            false,
            'en',
            null,
        );
    }

    private function makePlan(string $name, string $planId): Plan
    {
        $plan = new Plan();
        $plan->setName($name);
        $plan->setPlanId($planId);
        $plan->setPrice($planId === '0' ? 0 : 900);

        $reflection = new ReflectionProperty(Plan::class, 'id');
        $reflection->setValue($plan, new Ulid());

        return $plan;
    }

    private function makeSubscription(Plan $plan): Subscription
    {
        $subscription = new Subscription();
        $subscription->setPlan($plan);

        $reflection = new ReflectionProperty(Subscription::class, 'id');
        $reflection->setValue($subscription, new Ulid());

        return $subscription;
    }

    private function makeCreatedEvent(Ulid $subscriptionId, int $variantId): SubscriptionCreatedEvent
    {
        return new SubscriptionCreatedEvent($subscriptionId, 'lemon-' . $variantId, $this->makeLsSubscription($variantId));
    }

    private function makeUpdatedEvent(Ulid $subscriptionId, int $variantId): SubscriptionUpdatedEvent
    {
        return new SubscriptionUpdatedEvent($subscriptionId, 'lemon-' . $variantId, $this->makeLsSubscription($variantId));
    }

    private function makeLsSubscription(int $variantId): LemonSqueezySubscription
    {
        $attributes = new SubscriptionAttributes();
        $attributes->variantId = $variantId;

        $dto = new LemonSqueezySubscription();
        $dto->attributes = $attributes;

        return $dto;
    }
}
