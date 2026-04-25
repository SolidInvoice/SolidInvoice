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

namespace SolidInvoice\SaasBundle\Tests\Security\Voter;

use DateTimeImmutable;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Clock\ClockInterface;
use SolidInvoice\ApiBundle\Security\Attribute as ApiAttribute;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Security\Attribute as McpAttribute;
use SolidInvoice\SaasBundle\Security\Voter\SubscriptionVoter;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @covers \SolidInvoice\SaasBundle\Security\Voter\SubscriptionVoter
 *
 * @group functional
 */
final class SubscriptionVoterTest extends KernelTestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use EnsureApplicationInstalled;

    private const string TRIAL_REASON = 'Your trial has ended. Activate a subscription to continue using this resource.';

    private const string SUBSCRIPTION_ENDED_REASON = 'Your subscription has ended. Renew it to continue using this resource.';

    private const string PAUSED_REASON = 'Your subscription is currently paused. Reactivate it to continue using this resource.';

    private const string PENDING_REASON = 'Your subscription payment is still being processed. Access will resume once it completes.';

    private const string NO_COMPANY_REASON = 'No company is associated with this request.';

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideAttributes(): iterable
    {
        yield 'mcp attribute' => [McpAttribute::ACCESS];
        yield 'api attribute' => [ApiAttribute::ACCESS];
    }

    public function testAbstainsForUnsupportedAttribute(): void
    {
        $voter = $this->createVoter(saasEnabled: true);

        $result = $voter->vote(M::mock(TokenInterface::class), null, ['ROLE_USER']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsWhenSaasDisabled(string $attribute): void
    {
        $voter = $this->createVoter(saasEnabled: false);

        self::assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesWhenNoCompanySelected(string $attribute): void
    {
        self::getContainer()->get(CompanySelector::class)->reset();

        $voter = $this->createVoter(
            saasEnabled: true,
            subscription: $this->createSubscription(SubscriptionStatus::PAUSED),
        );

        self::assertDeniedWithReason($voter, $attribute, self::NO_COMPANY_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsWhenNoSubscription(string $attribute): void
    {
        $voter = $this->createVoter(saasEnabled: true, subscription: null);

        self::assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsWhenSubscriptionActive(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            subscription: $this->createSubscription(SubscriptionStatus::ACTIVE),
        );

        self::assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsTrialBeforeEndDate(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            now: new DateTimeImmutable('2026-01-10'),
            subscription: $this->createSubscription(SubscriptionStatus::TRIAL, new DateTimeImmutable('2026-01-15')),
        );

        self::assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesTrialAfterEndDate(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            now: new DateTimeImmutable('2026-01-20'),
            subscription: $this->createSubscription(SubscriptionStatus::TRIAL, new DateTimeImmutable('2026-01-15')),
        );

        self::assertDeniedWithReason($voter, $attribute, self::TRIAL_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsCancelledWithinGrace(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            now: new DateTimeImmutable('2026-01-10'),
            subscription: $this->createSubscription(SubscriptionStatus::CANCELLED, new DateTimeImmutable('2026-01-15')),
        );

        self::assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesCancelledAfterGrace(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            now: new DateTimeImmutable('2026-01-20'),
            subscription: $this->createSubscription(SubscriptionStatus::CANCELLED, new DateTimeImmutable('2026-01-15')),
        );

        self::assertDeniedWithReason($voter, $attribute, self::SUBSCRIPTION_ENDED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsExpiredWithinGrace(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            now: new DateTimeImmutable('2026-01-10'),
            subscription: $this->createSubscription(SubscriptionStatus::EXPIRED, new DateTimeImmutable('2026-01-15')),
        );

        self::assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesExpiredAfterGrace(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            now: new DateTimeImmutable('2026-01-20'),
            subscription: $this->createSubscription(SubscriptionStatus::EXPIRED, new DateTimeImmutable('2026-01-15')),
        );

        self::assertDeniedWithReason($voter, $attribute, self::SUBSCRIPTION_ENDED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesPaused(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            subscription: $this->createSubscription(SubscriptionStatus::PAUSED),
        );

        self::assertDeniedWithReason($voter, $attribute, self::PAUSED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesPending(string $attribute): void
    {
        $voter = $this->createVoter(
            saasEnabled: true,
            subscription: $this->createSubscription(SubscriptionStatus::PENDING),
        );

        self::assertDeniedWithReason($voter, $attribute, self::PENDING_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsOnUnexpectedException(string $attribute): void
    {
        $toggler = M::mock(ToggleInterface::class);
        $toggler->shouldReceive('isActive')->with('saas_enabled')->andThrow(new \RuntimeException('table missing'));

        $container = self::getContainer();

        $voter = new SubscriptionVoter(
            $toggler,
            M::mock(SubscriptionProviderInterface::class),
            $container->get(CompanySelector::class),
            $container->get(CompanyRepository::class),
            M::mock(ClockInterface::class),
        );

        self::assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    private static function assertVoteResult(int $expected, SubscriptionVoter $voter, string $attribute): void
    {
        self::assertSame($expected, $voter->vote(M::mock(TokenInterface::class), null, [$attribute]));
    }

    private static function assertDeniedWithReason(SubscriptionVoter $voter, string $attribute, string $reason): void
    {
        $vote = new Vote();
        $result = $voter->vote(M::mock(TokenInterface::class), null, [$attribute], $vote);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
        self::assertSame([$reason], $vote->reasons);
    }

    private function createVoter(
        bool $saasEnabled,
        ?Subscription $subscription = null,
        ?DateTimeImmutable $now = null,
    ): SubscriptionVoter {
        $container = self::getContainer();

        $toggler = M::mock(ToggleInterface::class);
        $toggler->shouldReceive('isActive')->with('saas_enabled')->andReturn($saasEnabled);

        $subscriptionProvider = M::mock(SubscriptionProviderInterface::class);
        $subscriptionProvider->shouldReceive('getSubscriptionFor')->andReturn($subscription);

        $clock = M::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn($now ?? new DateTimeImmutable('2026-01-01'));

        return new SubscriptionVoter(
            $toggler,
            $subscriptionProvider,
            $container->get(CompanySelector::class),
            $container->get(CompanyRepository::class),
            $clock,
        );
    }

    private function createSubscription(
        SubscriptionStatus $status,
        ?DateTimeImmutable $endDate = null,
    ): Subscription {
        $plan = new Plan();
        $plan->setName('Test Plan');
        $plan->setPlanId('test-plan-' . Ulid::generate());
        $plan->setPrice(1000);

        $subscription = new Subscription();
        $subscription->setSubscriber($this->company);
        $subscription->setPlan($plan);
        $subscription->setStatus($status);
        $subscription->setStartDate(new DateTimeImmutable('2026-01-01'));
        $subscription->setEndDate($endDate ?? new DateTimeImmutable('2026-12-31'));

        return $subscription;
    }
}
