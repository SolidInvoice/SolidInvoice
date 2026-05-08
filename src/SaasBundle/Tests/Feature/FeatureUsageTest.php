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

namespace SolidInvoice\SaasBundle\Tests\Feature;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SaasBundle\Feature\FeatureUsage;

/**
 * Edge cases pinned by US-002 acceptance criteria:
 *   total=10  → threshold=1   (10 * 0.1 = 1.0, ceil = 1)
 *   total=100 → threshold=10  (100 * 0.1 = 10.0, ceil = 10)
 *   total=3   → threshold=1   (3 * 0.1 = 0.3, ceil = 1, floor lifts to 1)
 */
#[CoversClass(FeatureUsage::class)]
final class FeatureUsageTest extends TestCase
{
    /**
     * @return iterable<string, array{int, int, bool}>
     */
    public static function provideApproachingLimitCases(): iterable
    {
        // total=10, threshold=1 — only "1 remaining" trips the alert.
        yield 'total=10 remaining=2 → not approaching' => [2, 10, false];
        yield 'total=10 remaining=1 → approaching' => [1, 10, true];
        yield 'total=10 remaining=0 → approaching (at limit)' => [0, 10, true];

        // total=100, threshold=10 — anything ≤ 10 trips the alert.
        yield 'total=100 remaining=11 → not approaching' => [11, 100, false];
        yield 'total=100 remaining=10 → approaching' => [10, 100, true];
        yield 'total=100 remaining=1 → approaching' => [1, 100, true];

        // total=3, threshold=1 — small quotas still get a 1-unit floor.
        yield 'total=3 remaining=2 → not approaching' => [2, 3, false];
        yield 'total=3 remaining=1 → approaching' => [1, 3, true];
        yield 'total=3 remaining=0 → approaching (at limit)' => [0, 3, true];

        // Pathological inputs — never blow up, never warn.
        yield 'total=0 → never approaching (no quota)' => [0, 0, false];
        yield 'negative remaining → never approaching' => [-1, 10, false];
    }

    #[DataProvider('provideApproachingLimitCases')]
    public function testIsApproachingLimit(int $remaining, int $total, bool $expected): void
    {
        $usage = new FeatureUsage();

        self::assertSame($expected, $usage->isApproachingLimit($remaining, $total));
    }
}
