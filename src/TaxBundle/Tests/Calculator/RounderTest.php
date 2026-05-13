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

namespace SolidInvoice\TaxBundle\Tests\Calculator;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\TestCase;
use SolidInvoice\TaxBundle\Calculator\Rounder;
use SolidInvoice\TaxBundle\Enum\RoundingStrategy;

final class RounderTest extends TestCase
{
    public function testHalfEvenIsTheDefaultStrategy(): void
    {
        $rounder = new Rounder();

        self::assertSame(RoundingStrategy::HalfEven, $rounder->getStrategy());
    }

    public function testHalfEvenRoundsToNearestEvenAtTheHalfwayMark(): void
    {
        $rounder = new Rounder(RoundingStrategy::HalfEven);

        self::assertTrue($rounder->round('0.5')->isEqualTo(BigDecimal::of('0')));
        self::assertTrue($rounder->round('1.5')->isEqualTo(BigDecimal::of('2')));
        self::assertTrue($rounder->round('2.5')->isEqualTo(BigDecimal::of('2')));
        self::assertTrue($rounder->round('3.5')->isEqualTo(BigDecimal::of('4')));
    }

    public function testHalfUpAlwaysRoundsAwayFromZeroAtTheHalfwayMark(): void
    {
        $rounder = new Rounder(RoundingStrategy::HalfUp);

        self::assertTrue($rounder->round('0.5')->isEqualTo(BigDecimal::of('1')));
        self::assertTrue($rounder->round('1.5')->isEqualTo(BigDecimal::of('2')));
        self::assertTrue($rounder->round('2.5')->isEqualTo(BigDecimal::of('3')));
    }

    public function testHalfDownAlwaysRoundsTowardsZeroAtTheHalfwayMark(): void
    {
        $rounder = new Rounder(RoundingStrategy::HalfDown);

        self::assertTrue($rounder->round('0.5')->isEqualTo(BigDecimal::of('0')));
        self::assertTrue($rounder->round('1.5')->isEqualTo(BigDecimal::of('1')));
        self::assertTrue($rounder->round('2.5')->isEqualTo(BigDecimal::of('2')));
    }

    public function testUpRoundsAwayFromZero(): void
    {
        $rounder = new Rounder(RoundingStrategy::Up);

        self::assertTrue($rounder->round('0.1')->isEqualTo(BigDecimal::of('1')));
        self::assertTrue($rounder->round('1.0001')->isEqualTo(BigDecimal::of('2')));
    }

    public function testDownRoundsTowardsZero(): void
    {
        $rounder = new Rounder(RoundingStrategy::Down);

        self::assertTrue($rounder->round('0.9')->isEqualTo(BigDecimal::of('0')));
        self::assertTrue($rounder->round('1.9999')->isEqualTo(BigDecimal::of('1')));
    }

    public function testRoundingToHigherScale(): void
    {
        $rounder = new Rounder(RoundingStrategy::HalfEven);

        self::assertTrue($rounder->round('1.235', 2)->isEqualTo(BigDecimal::of('1.24')));
        self::assertTrue($rounder->round('1.225', 2)->isEqualTo(BigDecimal::of('1.22')));
    }

    public function testRoundingHandlesNegativeValues(): void
    {
        $rounder = new Rounder(RoundingStrategy::HalfEven);

        self::assertTrue($rounder->round('-2.5')->isEqualTo(BigDecimal::of('-2')));
        self::assertTrue($rounder->round('-3.5')->isEqualTo(BigDecimal::of('-4')));
    }

    public function testWithStrategyReturnsANewInstance(): void
    {
        $original = new Rounder(RoundingStrategy::HalfEven);
        $derived = $original->withStrategy(RoundingStrategy::HalfUp);

        self::assertSame(RoundingStrategy::HalfEven, $original->getStrategy());
        self::assertSame(RoundingStrategy::HalfUp, $derived->getStrategy());
    }
}
