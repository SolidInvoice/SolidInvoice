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

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use SolidInvoice\SaasBundle\Service\TrialBanner;
use SolidInvoice\SaasBundle\Service\TrialBannerResolver;
use SolidWorx\Platform\SaasBundle\Entity\Plan;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;
use SolidWorx\Platform\SaasBundle\Enum\SubscriptionStatus;

#[CoversClass(TrialBannerResolver::class)]
#[CoversClass(TrialBanner::class)]
final class TrialBannerResolverTest extends TestCase
{
    private const string NOW = '2024-01-01';

    public function testNoBannerWhenTrialHasMoreThanBannerDaysRemaining(): void
    {
        // 9 days remaining, threshold 7
        $banner = $this->resolve(SubscriptionStatus::TRIAL, '2024-01-10');

        self::assertNull($banner);
    }

    public function testExtendVariantWithinBannerWindow(): void
    {
        // 5 days remaining: <= 7 (banner) and > 2 (no coupon)
        $banner = $this->resolve(SubscriptionStatus::TRIAL, '2024-01-06');

        self::assertInstanceOf(TrialBanner::class, $banner);
        self::assertSame('info', $banner->type);
        self::assertSame('saas.trial_banner.extend.title', $banner->titleKey);
        self::assertSame(['%days%' => 14], $banner->params);
        self::assertNull($banner->code);
    }

    public function testCouponVariantInFinalDaysWhenCodeConfigured(): void
    {
        // 2 days remaining: <= 2 (coupon)
        $banner = $this->resolve(SubscriptionStatus::TRIAL, '2024-01-03', couponCode: 'SAVE30');

        self::assertInstanceOf(TrialBanner::class, $banner);
        self::assertSame('saas.trial_banner.coupon.title', $banner->titleKey);
        self::assertSame(
            ['%days%' => 14, '%percent%' => 30, '%code%' => 'SAVE30'],
            $banner->params,
        );
        self::assertSame('SAVE30', $banner->code);
    }

    public function testExtendVariantInFinalDaysWhenNoCouponConfigured(): void
    {
        $banner = $this->resolve(SubscriptionStatus::TRIAL, '2024-01-03', couponCode: '');

        self::assertInstanceOf(TrialBanner::class, $banner);
        self::assertSame('saas.trial_banner.extend.title', $banner->titleKey);
        self::assertNull($banner->code);
    }

    public function testNoBannerWhenTrialExternallyBilled(): void
    {
        $banner = $this->resolve(SubscriptionStatus::TRIAL, '2024-01-06', subscriptionId: 'ls_1');

        self::assertNull($banner);
    }

    public function testNoBannerWhenTrialExpired(): void
    {
        $banner = $this->resolve(SubscriptionStatus::TRIAL, '2023-12-30');

        self::assertNull($banner);
    }

    public function testNoBannerForActiveSubscription(): void
    {
        $banner = $this->resolve(SubscriptionStatus::ACTIVE, '2024-01-06');

        self::assertNull($banner);
    }

    public function testCancelledBannerWhenNotExpired(): void
    {
        $banner = $this->resolve(SubscriptionStatus::CANCELLED, '2024-01-06');

        self::assertInstanceOf(TrialBanner::class, $banner);
        self::assertSame('danger', $banner->type);
        self::assertSame('saas.trial_banner.cancelled.title', $banner->titleKey);
        self::assertSame(['%date%' => 'January 6, 2024'], $banner->params);
        self::assertNull($banner->code);
    }

    public function testNoBannerWhenCancelledExpired(): void
    {
        $banner = $this->resolve(SubscriptionStatus::CANCELLED, '2023-12-30');

        self::assertNull($banner);
    }

    private function resolve(
        SubscriptionStatus $status,
        string $endDate,
        string $couponCode = 'SAVE30',
        ?string $subscriptionId = null,
    ): ?TrialBanner {
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new DateTimeImmutable(self::NOW));

        $resolver = new TrialBannerResolver(
            $clock,
            couponCode: $couponCode,
            couponPercent: 30,
            bannerDays: 7,
            couponDays: 2,
            extensionDays: 14,
        );

        $plan = new Plan();
        $plan->setName('Pro');
        $plan->setPlanId('variant-123');
        $plan->setPrice(1000);

        $subscription = new Subscription();
        $subscription->setPlan($plan);
        $subscription->setStatus($status);
        $subscription->setStartDate(CarbonImmutable::parse('2023-12-25'));
        $subscription->setEndDate(new DateTimeImmutable($endDate));
        $subscription->setSubscriptionId($subscriptionId);

        return $resolver->resolve($subscription);
    }
}
