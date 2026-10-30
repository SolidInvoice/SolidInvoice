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

namespace SolidInvoice\MoneyBundle\Tests\Currency;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;

#[CoversClass(CurrencyScale::class)]
final class CurrencyScaleTest extends TestCase
{
    private CurrencyScale $scale;

    protected function setUp(): void
    {
        $this->scale = new CurrencyScale();
    }

    #[DataProvider('subunitProvider')]
    public function testSubunitFor(string $currency, int $expected): void
    {
        self::assertSame($expected, $this->scale->subunitFor(new Currency($currency)));
    }

    #[DataProvider('factorProvider')]
    public function testFactorFor(string $currency, int $expected): void
    {
        self::assertSame($expected, $this->scale->factorFor(new Currency($currency)));
    }

    /**
     * @throws MathException
     */
    #[DataProvider('toMinorUnitProvider')]
    public function testToMinorUnit(string $currency, float | int | string $value, string $expected): void
    {
        self::assertTrue(
            $this->scale->toMinorUnit($value, new Currency($currency))->isEqualTo(BigDecimal::of($expected))
        );
    }

    /**
     * @throws MathException
     */
    #[DataProvider('toMajorUnitProvider')]
    public function testToMajorUnit(string $currency, float | int | string $value, float $expected): void
    {
        self::assertSame($expected, $this->scale->toMajorUnit($value, new Currency($currency)));
    }

    /**
     * A currency outside the ISO set must not blow up a request; it falls back to two decimals.
     *
     * @throws MathException
     */
    public function testUnknownCurrencyFallsBackToTwoDecimals(): void
    {
        $unknown = new Currency('XYZ');

        self::assertSame(2, $this->scale->subunitFor($unknown));
        self::assertSame(100, $this->scale->factorFor($unknown));
        self::assertSame(12.34, $this->scale->toMajorUnit(1234, $unknown));
    }

    /**
     * Converting to minor units and back must be lossless for every scale.
     *
     * @throws MathException
     */
    #[DataProvider('roundTripProvider')]
    public function testRoundTripIsLossless(string $currency, float $value): void
    {
        $currency = new Currency($currency);

        self::assertSame($value, $this->scale->toMajorUnit($this->scale->toMinorUnit($value, $currency), $currency));
    }

    /**
     * Pins the half-even contract {@see \SolidInvoice\TaxBundle\Twig\Breakdown\RenderedTaxSummaryRow}
     * leans on to reconcile the printed taxable base with the printed tax on an
     * inclusive line (SOL-560): the two must agree to the unit, so this is the single
     * place the rounding is defined.
     *
     * @throws MathException
     */
    #[DataProvider('toWholeMinorUnitsProvider')]
    public function testToWholeMinorUnits(float | int | string $value, string $expected): void
    {
        self::assertTrue($this->scale->toWholeMinorUnits($value)->isEqualTo(BigDecimal::of($expected)));
    }

    /**
     * @return iterable<string, array{float|int|string, string}>
     */
    public static function toWholeMinorUnitsProvider(): iterable
    {
        yield 'already whole' => [1200, '1200'];
        yield 'rounds down (not a tie)' => ['1200.49', '1200'];
        yield 'rounds up (not a tie)' => ['1200.51', '1201'];
        yield 'half-even tie resolves down to the even neighbour' => ['2.5', '2'];
        yield 'half-even tie resolves up to the even neighbour' => ['3.5', '4'];
    }

    /**
     * @return iterable<array{string, int}>
     */
    public static function subunitProvider(): iterable
    {
        yield 'two decimals' => ['USD', 2];
        yield 'two decimals (EUR)' => ['EUR', 2];
        yield 'zero decimals' => ['JPY', 0];
        yield 'zero decimals (KRW)' => ['KRW', 0];
        yield 'three decimals' => ['BHD', 3];
        yield 'three decimals (KWD)' => ['KWD', 3];
    }

    /**
     * @return iterable<array{string, int}>
     */
    public static function factorProvider(): iterable
    {
        yield ['USD', 100];
        yield ['JPY', 1];
        yield ['BHD', 1000];
    }

    /**
     * @return iterable<array{string, float|int|string, string}>
     */
    public static function toMinorUnitProvider(): iterable
    {
        yield 'USD 10.00' => ['USD', 10.00, '1000'];
        yield 'USD 10.99' => ['USD', 10.99, '1099'];
        yield 'JPY 8' => ['JPY', 8, '8'];
        yield 'JPY 800' => ['JPY', 800, '800'];
        yield 'BHD 1.234' => ['BHD', 1.234, '1234'];
        yield 'BHD 10' => ['BHD', 10, '10000'];
    }

    /**
     * @return iterable<array{string, float|int|string, float}>
     */
    public static function toMajorUnitProvider(): iterable
    {
        yield 'USD 1000' => ['USD', 1000, 10.0];
        yield 'USD 1099' => ['USD', 1099, 10.99];
        yield 'JPY 8' => ['JPY', 8, 8.0];
        yield 'JPY 800' => ['JPY', 800, 800.0];
        yield 'BHD 1234' => ['BHD', 1234, 1.234];
        yield 'BHD 10000' => ['BHD', 10000, 10.0];
    }

    /**
     * @return iterable<array{string, float}>
     */
    public static function roundTripProvider(): iterable
    {
        yield ['USD', 12.34];
        yield ['JPY', 800.0];
        yield ['BHD', 1.234];
    }
}
