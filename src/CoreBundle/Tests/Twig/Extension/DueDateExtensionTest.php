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
use SolidInvoice\CoreBundle\Date\DueDateCalculator;
use SolidInvoice\CoreBundle\Twig\Extension\DueDateExtension;
use Symfony\Component\Clock\MockClock;

final class DueDateExtensionTest extends TestCase
{
    public function testDaysUntilDueDelegatesToTheCalculator(): void
    {
        $clock = new MockClock(new DateTimeImmutable('2026-09-28 10:00:00', new DateTimeZone('UTC')));
        $extension = new DueDateExtension(new DueDateCalculator($clock));

        self::assertSame(
            1,
            $extension->daysUntilDue(new DateTimeImmutable('2026-09-29 00:00:00', new DateTimeZone('UTC'))),
        );
    }

    public function testDaysUntilDueDelegatesNullToTheCalculator(): void
    {
        $clock = new MockClock(new DateTimeImmutable('2026-09-28 10:00:00', new DateTimeZone('UTC')));
        $extension = new DueDateExtension(new DueDateCalculator($clock));

        self::assertNull($extension->daysUntilDue(null));
    }
}
