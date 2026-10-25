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

namespace SolidInvoice\CoreBundle\Tests\Entity;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Entity\BillingPeriod;

#[CoversClass(BillingPeriod::class)]
final class BillingPeriodTest extends TestCase
{
    public function testBothDatesAreComplete(): void
    {
        $period = BillingPeriod::of(CarbonImmutable::parse('2026-02-01'), CarbonImmutable::parse('2026-02-28'));

        self::assertTrue($period->isComplete());
        self::assertFalse($period->isEmpty());
    }

    public function testNeitherDateIsEmpty(): void
    {
        $period = BillingPeriod::of(null, null);

        self::assertFalse($period->isComplete());
        self::assertTrue($period->isEmpty());
    }

    public function testStartOnlyIsNeitherCompleteNorEmpty(): void
    {
        $period = BillingPeriod::of(CarbonImmutable::parse('2026-02-01'), null);

        self::assertFalse($period->isComplete());
        self::assertFalse($period->isEmpty());
    }

    public function testEndOnlyIsNeitherCompleteNorEmpty(): void
    {
        $period = BillingPeriod::of(null, CarbonImmutable::parse('2026-02-28'));

        self::assertFalse($period->isComplete());
        self::assertFalse($period->isEmpty());
    }
}
