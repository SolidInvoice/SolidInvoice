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

namespace SolidInvoice\SaasBundle\Tests\Company;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Company\UserEligibleCompanies;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\SaasBundle\Company\SubscriptionAwareUserCompanies;
use SolidInvoice\SaasBundle\Service\SubscriptionEligibility;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;
use SolidWorx\Platform\SaasBundle\Subscription\SubscriptionProviderInterface;

/**
 * @covers \SolidInvoice\SaasBundle\Company\SubscriptionAwareUserCompanies
 */
final class SubscriptionAwareUserCompaniesTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testKeepsCompaniesWithEligibleSubscription(): void
    {
        $active = new Company();
        $trial = new Company();
        $expiredPaused = new Company();

        $subscriptions = new \SplObjectStorage();
        $subscriptions[$active] = $this->subscription(SubscriptionStatus::ACTIVE, '2099-12-31');
        $subscriptions[$trial] = $this->subscription(SubscriptionStatus::TRIAL, '2099-12-31');
        $subscriptions[$expiredPaused] = $this->subscription(SubscriptionStatus::PAUSED, '2099-12-31');

        $decorator = $this->buildDecorator(
            inner: [$active, $trial, $expiredPaused],
            subscriptions: $subscriptions,
        );

        $result = $decorator->getFor(new User());

        self::assertSame([$active, $trial], $result);
    }

    public function testReindexesAsList(): void
    {
        $eligible = new Company();
        $denied = new Company();

        $subscriptions = new \SplObjectStorage();
        $subscriptions[$eligible] = $this->subscription(SubscriptionStatus::ACTIVE, '2099-12-31');
        $subscriptions[$denied] = $this->subscription(SubscriptionStatus::PAUSED, '2099-12-31');

        // Pass denied first to force a non-zero key after array_filter.
        $decorator = $this->buildDecorator(
            inner: [$denied, $eligible],
            subscriptions: $subscriptions,
        );

        $result = $decorator->getFor(new User());

        self::assertSame([$eligible], $result);
        self::assertSame([0], array_keys($result));
    }

    public function testReturnsEmptyWhenInnerIsEmpty(): void
    {
        $decorator = $this->buildDecorator(inner: [], subscriptions: new \SplObjectStorage());

        self::assertSame([], $decorator->getFor(new User()));
    }

    public function testReturnsEmptyWhenAllCompaniesAreIneligible(): void
    {
        $denied = new Company();

        $subscriptions = new \SplObjectStorage();
        $subscriptions[$denied] = $this->subscription(SubscriptionStatus::PAUSED, '2099-12-31');

        $decorator = $this->buildDecorator(inner: [$denied], subscriptions: $subscriptions);

        self::assertSame([], $decorator->getFor(new User()));
    }

    /**
     * @param list<Company>                    $inner
     * @param \SplObjectStorage<Company, ?Subscription> $subscriptions
     */
    private function buildDecorator(array $inner, \SplObjectStorage $subscriptions): SubscriptionAwareUserCompanies
    {
        $innerProvider = M::mock(UserEligibleCompanies::class);
        $innerProvider->shouldReceive('getFor')->andReturn($inner);

        $provider = M::mock(SubscriptionProviderInterface::class);
        $provider->shouldReceive('getSubscriptionFor')
            ->andReturnUsing(static fn (Company $c): ?Subscription => $subscriptions[$c] ?? null);

        $clock = M::mock(ClockInterface::class);
        $clock->shouldReceive('now')->andReturn(new DateTimeImmutable('2026-06-01'));

        return new SubscriptionAwareUserCompanies(
            $innerProvider,
            new SubscriptionEligibility($provider, $clock),
        );
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
