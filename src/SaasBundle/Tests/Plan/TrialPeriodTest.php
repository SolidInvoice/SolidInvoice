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

namespace SolidInvoice\SaasBundle\Tests\Plan;

use DateInterval;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Plan\TrialPeriod;
use SolidWorx\Platform\SaasBundle\Entity\Plan;

#[CoversClass(TrialPeriod::class)]
final class TrialPeriodTest extends TestCase
{
    public function testAPlanWithoutATrialHasNoDays(): void
    {
        self::assertNull(new TrialPeriod()->days(new Plan()));
    }

    /**
     * A zero-length trial is the "no trial, bill immediately" configuration and
     * must not read as a one-day trial in the UI.
     */
    public function testAZeroLengthTrialHasNoDays(): void
    {
        self::assertNull(new TrialPeriod()->days($this->planWithTrial('PT0S')));
    }

    #[DataProvider('trialDurations')]
    public function testTrialDurationIsReducedToWholeDays(string $duration, int $expected): void
    {
        self::assertSame($expected, new TrialPeriod()->days($this->planWithTrial($duration)));
    }

    /**
     * A calendar-month trial has no exact day count without a reference date;
     * Carbon cascades it at 4 weeks per month. Payment providers configure
     * trials in days, so this only matters if a month interval is ever set by
     * hand — assert the shape rather than pinning Carbon's factor.
     */
    public function testAMonthLongTrialIsApproximatedInDays(): void
    {
        $days = new TrialPeriod()->days($this->planWithTrial('P1M'));

        self::assertNotNull($days);
        self::assertGreaterThanOrEqual(28, $days);
        self::assertLessThanOrEqual(31, $days);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function trialDurations(): iterable
    {
        yield 'days' => ['P14D', 14];
        yield 'a single day' => ['P1D', 1];
        yield 'a fortnight' => ['P2W', 14];
        yield 'hours rounding down' => ['PT36H', 1];
    }

    private function planWithTrial(string $duration): Plan
    {
        $plan = new Plan();
        $plan->setTrialDuration(new DateInterval($duration));

        return $plan;
    }
}
