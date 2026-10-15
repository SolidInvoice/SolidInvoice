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

namespace SolidInvoice\CoreBundle\Tests\Twig\Extension;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Twig\Extension\DueDateExtension;
use Symfony\Component\Clock\MockClock;

/**
 * The clock is frozen at 10:00:00, away from midnight, because a boundary test
 * frozen at midnight would pass under both the correct calendar-day semantics
 * and the old, wrong timestamp-division semantics.
 */
final class DueDateExtensionTest extends TestCase
{
    private DueDateExtension $extension;

    private MockClock $clock;

    protected function setUp(): void
    {
        $this->clock = new MockClock(new DateTimeImmutable('2026-09-28 10:00:00', new DateTimeZone('UTC')));
        $this->extension = new DueDateExtension($this->clock);
    }

    public function testDueToday(): void
    {
        self::assertSame(0, $this->extension->daysUntilDue(new DateTimeImmutable('2026-09-28 00:00:00', new DateTimeZone('UTC'))));
    }

    public function testDueYesterday(): void
    {
        self::assertSame(-1, $this->extension->daysUntilDue(new DateTimeImmutable('2026-09-27 00:00:00', new DateTimeZone('UTC'))));
    }

    public function testDueTomorrow(): void
    {
        self::assertSame(1, $this->extension->daysUntilDue(new DateTimeImmutable('2026-09-29 00:00:00', new DateTimeZone('UTC'))));
    }

    public function testDueNull(): void
    {
        self::assertNull($this->extension->daysUntilDue(null));
    }

    public function testDueSevenDaysOut(): void
    {
        self::assertSame(7, $this->extension->daysUntilDue(new DateTimeImmutable('2026-10-05 00:00:00', new DateTimeZone('UTC'))));
    }

    /**
     * Europe/Berlin moved clocks forward on 2026-03-29, so the calendar gap
     * between the two dates is 2 whole days even though only 47 wall-clock
     * hours separate them. A timestamp-division implementation would floor
     * that to 1 day.
     */
    public function testDoesNotDriftAcrossADaylightSavingTransition(): void
    {
        $timezone = new DateTimeZone('Europe/Berlin');
        $this->clock = new MockClock(new DateTimeImmutable('2026-03-28 10:00:00', $timezone));
        $this->extension = new DueDateExtension($this->clock);

        $due = new DateTimeImmutable('2026-03-30 00:00:00', $timezone);

        self::assertSame(2, $this->extension->daysUntilDue($due));
    }
}
