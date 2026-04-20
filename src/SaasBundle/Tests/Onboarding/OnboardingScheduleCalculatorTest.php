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

namespace SolidInvoice\SaasBundle\Tests\Onboarding;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Onboarding\OnboardingScheduleCalculator;
use SolidWorx\Platform\SaasBundle\Entity\Subscription;

final class OnboardingScheduleCalculatorTest extends TestCase
{
    public function testFirstStepTargetsTrialStart(): void
    {
        $calculator = new OnboardingScheduleCalculator();

        $subscription = $this->subscription('2025-01-01 00:00:00', '2025-01-08 00:00:00');

        self::assertSame(
            '2025-01-01 00:00:00',
            $calculator->targetTimeFor($subscription, 0, 7)->format('Y-m-d H:i:s'),
        );
    }

    public function testStepsSpreadEvenlyAcross7DayTrial(): void
    {
        $calculator = new OnboardingScheduleCalculator();
        $subscription = $this->subscription('2025-01-01 00:00:00', '2025-01-08 00:00:00');

        // 7 steps over 7 days: spacing = 1 day
        self::assertSame('2025-01-02 00:00:00', $calculator->targetTimeFor($subscription, 1, 7)->format('Y-m-d H:i:s'));
        self::assertSame('2025-01-04 00:00:00', $calculator->targetTimeFor($subscription, 3, 7)->format('Y-m-d H:i:s'));
        self::assertSame('2025-01-07 00:00:00', $calculator->targetTimeFor($subscription, 6, 7)->format('Y-m-d H:i:s'));
    }

    public function testStepsSpreadProportionallyAcross14DayTrial(): void
    {
        $calculator = new OnboardingScheduleCalculator();
        $subscription = $this->subscription('2025-01-01 00:00:00', '2025-01-15 00:00:00');

        // 7 steps over 14 days: spacing = 2 days
        self::assertSame('2025-01-03 00:00:00', $calculator->targetTimeFor($subscription, 1, 7)->format('Y-m-d H:i:s'));
        self::assertSame('2025-01-13 00:00:00', $calculator->targetTimeFor($subscription, 6, 7)->format('Y-m-d H:i:s'));
    }

    public function testSingleStepAlwaysTargetsTrialStart(): void
    {
        $calculator = new OnboardingScheduleCalculator();
        $subscription = $this->subscription('2025-01-01 00:00:00', '2025-01-08 00:00:00');

        self::assertSame(
            '2025-01-01 00:00:00',
            $calculator->targetTimeFor($subscription, 0, 1)->format('Y-m-d H:i:s'),
        );
    }

    private function subscription(string $start, string $end): Subscription
    {
        $subscription = new Subscription();
        $subscription->setStartDate(new DateTimeImmutable($start));
        $subscription->setEndDate(new DateTimeImmutable($end));

        return $subscription;
    }
}
