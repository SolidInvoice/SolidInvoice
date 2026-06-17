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

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Clock\ClockInterface;
use RuntimeException;
use SolidInvoice\ApiBundle\Security\Attribute as ApiAttribute;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\McpBundle\Security\Attribute as McpAttribute;
use SolidInvoice\SaasBundle\Security\Voter\SubscriptionVoter;
use SolidInvoice\SaasBundle\Service\SubscriptionEligibility;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Platform\PlatformBundle\Feature\NoopFeatureGate;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Ulid;

#[CoversClass(SubscriptionVoter::class)]
#[Group('functional')]
final class SubscriptionVoterTest extends KernelTestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use EnsureApplicationInstalled;

    // API and MCP are paid-only, so trial/free/paused/pending all share this denial reason.
    private const string PAID_REQUIRED_REASON = 'A paid subscription is required to access this resource.';

    private const string SUBSCRIPTION_CHECK_FAILED_REASON = 'Unable to verify your subscription. Please try again later.';

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
        $voter = $this->createVoter();

        $result = $voter->vote(M::mock(TokenInterface::class), null, ['ROLE_USER']);

        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesWhenNoCompanySelected(string $attribute): void
    {
        self::getContainer()->get(CompanySelector::class)->reset();

        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::PAUSED),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::NO_COMPANY_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesWhenNoSubscription(string $attribute): void
    {
        // No subscription is not paid access, so API/MCP are denied.
        $voter = $this->createVoter(subscription: null);

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsWhenSubscriptionActive(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::ACTIVE),
        );

        $this->assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesTrialBeforeEndDate(string $attribute): void
    {
        // A trial is never paid access, even before it ends.
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::TRIAL, CarbonImmutable::parse('2026-01-15')),
            now: CarbonImmutable::parse('2026-01-10'),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesTrialAfterEndDate(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::TRIAL, CarbonImmutable::parse('2026-01-15')),
            now: CarbonImmutable::parse('2026-01-20'),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsCancelledWithinGrace(string $attribute): void
    {
        // A cancelled subscription is still paid until the already-paid term elapses.
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::CANCELLED, CarbonImmutable::parse('2026-01-15')),
            now: CarbonImmutable::parse('2026-01-10'),
        );

        $this->assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesCancelledAfterGrace(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::CANCELLED, CarbonImmutable::parse('2026-01-15')),
            now: CarbonImmutable::parse('2026-01-20'),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsExpiredWithinGrace(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::EXPIRED, CarbonImmutable::parse('2026-01-15')),
            now: CarbonImmutable::parse('2026-01-10'),
        );

        $this->assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesExpiredAfterGrace(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::EXPIRED, CarbonImmutable::parse('2026-01-15')),
            now: CarbonImmutable::parse('2026-01-20'),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesPaused(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::PAUSED),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesPending(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::PENDING),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesWhenSubscriptionProviderThrows(string $attribute): void
    {
        // The voter guards paid-only access and must fail closed on any error.
        $provider = M::mock(SubscriptionProviderInterface::class);
        $provider->shouldReceive('getSubscriptionFor')
            ->andThrow(new RuntimeException('subscription table missing'));

        $container = self::getContainer();

        $voter = new SubscriptionVoter(
            new SubscriptionEligibility($provider, M::mock(ClockInterface::class)),
            $container->get(CompanySelector::class),
            $container->get(CompanyRepository::class),
            new NoopFeatureGate(),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::SUBSCRIPTION_CHECK_FAILED_REASON);
    }

    #[DataProvider('provideAttributes')]
    public function testDeniesUnhandledSubscriptionStatus(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::PAST_DUE),
        );

        $this->assertDeniedWithReason($voter, $attribute, self::PAID_REQUIRED_REASON);
    }

    public function testDeniesWhenRestApiAccessFeatureIsDisabled(): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::ACTIVE),
            featureGate: $this->featureGateDenying('rest_api_access'),
        );

        $this->assertDeniedWithReason($voter, ApiAttribute::ACCESS, 'REST API access is not available on the current plan.');
    }

    public function testDeniesWhenMcpAccessFeatureIsDisabled(): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::ACTIVE),
            featureGate: $this->featureGateDenying('mcp_access'),
        );

        $this->assertDeniedWithReason($voter, McpAttribute::ACCESS, 'MCP access is not available on the current plan.');
    }

    #[DataProvider('provideAttributes')]
    public function testGrantsWhenSubscriptionActiveAndFeatureGateAllows(string $attribute): void
    {
        $voter = $this->createVoter(
            subscription: $this->createSubscription(SubscriptionStatus::ACTIVE),
            featureGate: new NoopFeatureGate(),
        );

        $this->assertVoteResult(VoterInterface::ACCESS_GRANTED, $voter, $attribute);
    }

    private function featureGateDenying(string $disabledKey): FeatureGate
    {
        $featureGate = M::mock(FeatureGate::class);
        $featureGate->shouldReceive('isEnabled')
            ->withArgs(static fn (string $key, ?object $_for): bool => $key === $disabledKey)
            ->andReturn(false);
        $featureGate->shouldReceive('isEnabled')->andReturn(true);

        return $featureGate;
    }

    private function assertVoteResult(int $expected, SubscriptionVoter $voter, string $attribute): void
    {
        self::assertSame($expected, $voter->vote(M::mock(TokenInterface::class), null, [$attribute]));
    }

    private function assertDeniedWithReason(SubscriptionVoter $voter, string $attribute, string $reason): void
    {
        $vote = new Vote();
        $result = $voter->vote(M::mock(TokenInterface::class), null, [$attribute], $vote);

        self::assertSame(VoterInterface::ACCESS_DENIED, $result);
        self::assertSame([$reason], $vote->reasons);
    }

    private function createVoter(
        ?Subscription $subscription = null,
        ?DateTimeImmutable $now = null,
        ?FeatureGate $featureGate = null,
    ): SubscriptionVoter {
        $container = self::getContainer();

        $subscriptionProvider = M::mock(SubscriptionProviderInterface::class);
        $subscriptionProvider->shouldReceive('getSubscriptionFor')->andReturn($subscription);

        $clock = M::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn($now ?? CarbonImmutable::parse('2026-01-01'));

        return new SubscriptionVoter(
            new SubscriptionEligibility($subscriptionProvider, $clock),
            $container->get(CompanySelector::class),
            $container->get(CompanyRepository::class),
            $featureGate ?? $container->get(FeatureGate::class),
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
        $subscription->setStartDate(CarbonImmutable::parse('2026-01-01'));
        $subscription->setEndDate($endDate ?? CarbonImmutable::parse('2026-12-31'));

        return $subscription;
    }
}
