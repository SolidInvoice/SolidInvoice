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

namespace SolidInvoice\MoneyBundle\Tests\Form\DataTransformer;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Money\Currency;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Form\DataTransformer\ViewTransformer;

#[CoversClass(ViewTransformer::class)]
final class ViewTransformerTest extends TestCase
{
    private ViewTransformer $viewTransformer;

    private ViewTransformer $jpyTransformer;

    private ViewTransformer $bhdTransformer;

    protected function setUp(): void
    {
        $this->viewTransformer = new ViewTransformer(new Currency('USD'));
        $this->jpyTransformer = new ViewTransformer(new Currency('JPY'));
        $this->bhdTransformer = new ViewTransformer(new Currency('BHD'));
    }

    #[DataProvider('reverseTransformDataProvider')]
    public function testReverseTransform(?float $value, int $expected): void
    {
        $result = $this->viewTransformer->reverseTransform($value);

        self::assertTrue($result->isEqualTo($expected));
    }

    /**
     * @throws DivisionByZeroException
     * @throws MathException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    #[DataProvider('transformDataProvider')]
    public function testTransformsMoneyObjectToFloat(BigNumber | string | int | float | null $money, float | int $expected): void
    {
        $value = $this->viewTransformer->transform($money);

        self::assertSame($expected, $value);
    }

    #[DataProvider('jpyReverseTransformDataProvider')]
    public function testReverseTransformForZeroDecimalCurrency(?int $value, int $expected): void
    {
        $result = $this->jpyTransformer->reverseTransform($value);

        self::assertTrue($result->isEqualTo($expected));
    }

    /**
     * @throws DivisionByZeroException
     * @throws MathException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    #[DataProvider('jpyTransformDataProvider')]
    public function testTransformForZeroDecimalCurrency(BigNumber | string | int | float | null $money, float | int $expected): void
    {
        $value = $this->jpyTransformer->transform($money);

        self::assertSame($expected, $value);
    }

    #[DataProvider('bhdReverseTransformDataProvider')]
    public function testReverseTransformForThreeDecimalCurrency(float | int | null $value, int $expected): void
    {
        $result = $this->bhdTransformer->reverseTransform($value);

        self::assertTrue($result->isEqualTo($expected));
    }

    /**
     * @throws DivisionByZeroException
     * @throws MathException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    #[DataProvider('bhdTransformDataProvider')]
    public function testTransformForThreeDecimalCurrency(BigNumber | string | int | float | null $money, float | int $expected): void
    {
        $value = $this->bhdTransformer->transform($money);

        self::assertSame($expected, $value);
    }

    /**
     * @return iterable<array<float|int|null>>
     */
    public static function reverseTransformDataProvider(): iterable
    {
        yield [null, 0];
        yield [10, 1000];
        yield [10.00, 1000];
        yield [10.01, 1001];
        yield [10.10, 1010];
        yield [10.11, 1011];
        yield [10.99, 1099];
        yield [111, 11100];
        yield [111.11, 11111];
        yield [0.01, 1];
        yield [0.10, 10];
        yield [0.11, 11];
        yield [0.99, 99];
    }

    /**
     * @return iterable<array<string|float|BigDecimal|null>>
     * @throws MathException
     */
    public static function transformDataProvider(): iterable
    {
        yield [null, 0.0];
        yield [1.0, 0.01];
        yield ['10.0', 0.10];
        yield [BigDecimal::of(1500), 15.0];
        yield [BigDecimal::of(1000), 10.0];
        yield [BigDecimal::of(100), 1.0];
        yield [BigDecimal::of(10), 0.10];
        yield [BigDecimal::of(1), 0.01];
        yield [BigDecimal::of(0), 0.0];
    }

    /**
     * @return iterable<array<int|null>>
     */
    public static function jpyReverseTransformDataProvider(): iterable
    {
        // For JPY (0 decimal places) the multiplier is 10^0 = 1, so input equals stored value.
        yield [null, 0];
        yield [8, 8];
        yield [100, 100];
        yield [1500, 1500];
    }

    /**
     * @return iterable<array<string|float|BigDecimal|null>>
     * @throws MathException
     */
    public static function jpyTransformDataProvider(): iterable
    {
        // For JPY (0 decimal places) the divisor is 10^0 = 1, so stored value equals display value.
        yield [null, 0.0];
        yield [BigDecimal::of(8), 8.0];
        yield [BigDecimal::of(100), 100.0];
        yield [BigDecimal::of(1500), 1500.0];
        yield [BigDecimal::of(0), 0.0];
    }

    /**
     * @return iterable<array<float|int|null>>
     */
    public static function bhdReverseTransformDataProvider(): iterable
    {
        // For BHD (3 decimal places) the multiplier is 10^3 = 1000.
        yield [null, 0];
        yield [1.234, 1234];
        yield [1, 1000];
        yield [0.001, 1];
        yield [10.5, 10500];
    }

    /**
     * @return iterable<array<string|float|BigDecimal|null>>
     * @throws MathException
     */
    public static function bhdTransformDataProvider(): iterable
    {
        // For BHD (3 decimal places) the divisor is 10^3 = 1000.
        yield [null, 0.0];
        yield [BigDecimal::of(1234), 1.234];
        yield [BigDecimal::of(1000), 1.0];
        yield [BigDecimal::of(1), 0.001];
        yield [BigDecimal::of(10500), 10.5];
        yield [BigDecimal::of(0), 0.0];
    }
}
