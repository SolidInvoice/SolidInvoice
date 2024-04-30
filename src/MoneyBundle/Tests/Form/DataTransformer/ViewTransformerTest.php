<?php

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
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Form\DataTransformer\ViewTransformer;

/**
 * @covers \SolidInvoice\MoneyBundle\Form\DataTransformer\ViewTransformer
 */
final class ViewTransformerTest extends TestCase
{
    private ViewTransformer $viewTransformer;

    protected function setUp(): void
    {
        $this->viewTransformer = new ViewTransformer();
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
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
     * @dataProvider transformDataProvider
     */
    public function testTransformsMoneyObjectToFloat(BigNumber|string|int|float|null $money, float | int $expected): void
    {
        $value = $this->viewTransformer->transform($money);

        self::assertSame($expected, $value);
    }

    /**
     * @return iterable<array<float|int|null>>
     */
    public function reverseTransformDataProvider(): iterable
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
     * @return iterable<array<string|float|null>>
     * @throws MathException
     */
    public function transformDataProvider(): iterable
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
}
