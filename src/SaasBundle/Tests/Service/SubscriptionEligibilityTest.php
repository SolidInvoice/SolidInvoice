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

namespace SolidInvoice\SaasBundle\Tests\Service;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SaasBundle\Service\EligibilityResult;
use SolidInvoice\SaasBundle\Service\SubscriptionEligibility;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;

/**
 * @covers \SolidInvoice\SaasBundle\Service\SubscriptionEligibility
 * @covers \SolidInvoice\SaasBundle\Service\EligibilityResult
 */
final class SubscriptionEligibilityTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const string NOW = '2026-06-01';

    public function testActiveWhenNoSubscriptionExists(): void
    {
        $eligibility = $this->makeEligibility(subscription: null);

        $result = $eligibility->evaluate(new Company());

        self::assertTrue($result->active);
        self::assertNull($result->reason);
    }

    public function testActiveStatusIsAlwaysAllowed(): void
    {
        // ACTIVE doesn't depend on endDate — set it in the past to confirm.
        $eligibility = $this->makeEligibility(
            $this->subscription(SubscriptionStatus::ACTIVE, '2020-01-01'),
        );

        self::assertTrue($eligibility->isActive(new Company()));
    }

    public function testTrialAllowedWhileEndDateInFuture(): void
    {
        $eligibility = $this->makeEligibility(
            $this->subscription(SubscriptionStatus::TRIAL, '2026-12-31'),
        );

        self::assertTrue($eligibility->isActive(new Company()));
    }

    public function testTrialDeniedWithReasonOnceEndDatePassed(): void
    {
        $eligibility = $this->makeEligibility(
            $this->subscription(SubscriptionStatus::TRIAL, '2026-05-01'),
        );

        $result = $eligibility->evaluate(new Company());

        self::assertFalse($result->active);
        self::assertSame(
            'Your trial has ended. Activate a subscription to continue using this resource.',
            $result->reason,
        );
    }

    /**
     * @return iterable<string, array{SubscriptionStatus}>
     */
    public static function provideGracePeriodStatuses(): iterable
    {
        yield 'cancelled' => [SubscriptionStatus::CANCELLED];
        yield 'expired' => [SubscriptionStatus::EXPIRED];
    }

    #[DataProvider('provideGracePeriodStatuses')]
    public function testGracePeriodAllowedWhileEndDateInFuture(SubscriptionStatus $status): void
    {
        $eligibility = $this->makeEligibility(
            $this->subscription($status, '2026-12-31'),
        );

        self::assertTrue($eligibility->isActive(new Company()));
    }

    #[DataProvider('provideGracePeriodStatuses')]
    public function testGracePeriodDeniedWithReasonOnceEndDatePassed(SubscriptionStatus $status): void
    {
        $eligibility = $this->makeEligibility(
            $this->subscription($status, '2026-05-01'),
        );

        $result = $eligibility->evaluate(new Company());

        self::assertFalse($result->active);
        self::assertSame(
            'Your subscription has ended. Renew it to continue using this resource.',
            $result->reason,
        );
    }

    public function testPausedAlwaysDenied(): void
    {
        $eligibility = $this->makeEligibility(
            $this->subscription(SubscriptionStatus::PAUSED, '2099-01-01'),
        );

        $result = $eligibility->evaluate(new Company());

        self::assertFalse($result->active);
        self::assertSame(
            'Your subscription is currently paused. Reactivate it to continue using this resource.',
            $result->reason,
        );
    }

    public function testPendingAlwaysDenied(): void
    {
        $eligibility = $this->makeEligibility(
            $this->subscription(SubscriptionStatus::PENDING, '2099-01-01'),
        );

        $result = $eligibility->evaluate(new Company());

        self::assertFalse($result->active);
        self::assertSame(
            'Your subscription payment is still being processed. Access will resume once it completes.',
            $result->reason,
        );
    }

    public function testUnhandledStatusFailsClosed(): void
    {
        $eligibility = $this->makeEligibility(
            $this->subscription(SubscriptionStatus::PAST_DUE, '2099-01-01'),
        );

        $result = $eligibility->evaluate(new Company());

        self::assertFalse($result->active);
        self::assertSame('Your subscription is not currently active.', $result->reason);
    }

    public function testEligibilityResultFactoryHelpers(): void
    {
        $active = EligibilityResult::active();
        self::assertTrue($active->active);
        self::assertNull($active->reason);

        $denied = EligibilityResult::denied('nope');
        self::assertFalse($denied->active);
        self::assertSame('nope', $denied->reason);
    }

    private function makeEligibility(?Subscription $subscription): SubscriptionEligibility
    {
        $provider = M::mock(SubscriptionProviderInterface::class);
        $provider->shouldReceive('getSubscriptionFor')->andReturn($subscription);

        $clock = M::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn(new DateTimeImmutable(self::NOW));

        return new SubscriptionEligibility($provider, $clock);
    }

    private function subscription(SubscriptionStatus $status, string $endDate): Subscription
    {
        $subscription = new Subscription();
        $subscription->setStatus($status);
        $subscription->setStartDate(new DateTimeImmutable('2025-01-01'));
        $subscription->setEndDate(new DateTimeImmutable($endDate));

        return $subscription;
    }
}
