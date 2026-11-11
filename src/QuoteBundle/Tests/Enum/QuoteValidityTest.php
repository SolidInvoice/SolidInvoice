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

namespace SolidInvoice\QuoteBundle\Tests\Enum;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\QuoteBundle\Enum\QuoteValidity;

#[CoversClass(QuoteValidity::class)]
final class QuoteValidityTest extends TestCase
{
    public function testPeriodCountsWholeDaysFromThePickedDay(): void
    {
        $from = CarbonImmutable::parse('2026-11-06 15:42:00');

        self::assertSame('2026-11-13', QuoteValidity::Days7->validUntil($from)?->format('Y-m-d'));
        self::assertSame('2026-12-06 00:00:00', QuoteValidity::Days30->validUntil($from)?->format('Y-m-d H:i:s'));
        self::assertNull(QuoteValidity::Custom->validUntil($from));
    }

    public function testResolve(): void
    {
        $from = CarbonImmutable::parse('2026-11-06');
        $picked = CarbonImmutable::parse('2027-01-15');

        self::assertNull(QuoteValidity::None->resolve($from, $picked));
        self::assertSame($picked, QuoteValidity::Custom->resolve($from, $picked));
        self::assertSame('2026-11-20', QuoteValidity::Days14->resolve($from, $picked)?->format('Y-m-d'));
    }

    public function testForDate(): void
    {
        self::assertSame(QuoteValidity::None, QuoteValidity::forDate(null));
        self::assertSame(QuoteValidity::Custom, QuoteValidity::forDate(CarbonImmutable::now()));
    }
}
