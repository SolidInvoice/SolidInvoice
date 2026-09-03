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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Service\BillingMode;
use SolidInvoice\SaasBundle\Tests\BillingModeFactory;

#[CoversClass(BillingMode::class)]
final class BillingModeTest extends TestCase
{
    public function testFreeTrialModeDoesNotRequireACard(): void
    {
        self::assertFalse(BillingModeFactory::freeTrial()->requiresCardForTrial());
    }

    public function testPaidTrialModeRequiresACard(): void
    {
        self::assertTrue(BillingModeFactory::paidTrial()->requiresCardForTrial());
    }

    public function testCouponIsOfferedInFreeTrialMode(): void
    {
        $billingMode = BillingModeFactory::freeTrial('SAVE30', 30);

        self::assertSame('SAVE30', $billingMode->couponCode());
        self::assertSame(30, $billingMode->couponPercent());
    }

    /**
     * A paid trial already has a card on file, so there is no coupon left to
     * redeem. Suppressing it here is what silences the banner, the expired-trial
     * page and the onboarding emails at once.
     */
    public function testCouponIsSuppressedInPaidTrialMode(): void
    {
        self::assertSame('', BillingModeFactory::paidTrial('SAVE30', 30)->couponCode());
    }

    public function testAnUnconfiguredCouponIsEmptyInEitherMode(): void
    {
        self::assertSame('', BillingModeFactory::freeTrial()->couponCode());
        self::assertSame('', BillingModeFactory::paidTrial()->couponCode());
    }
}
