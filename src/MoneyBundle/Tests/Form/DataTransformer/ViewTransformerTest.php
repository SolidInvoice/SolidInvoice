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

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Form\DataTransformer\ViewTransformer;

/**
 * @covers \SolidInvoice\MoneyBundle\Form\DataTransformer\ViewTransformer
 */
final class ViewTransformerTest extends TestCase
{
    private ViewTransformer $viewTransformer;

    private Currency $currency;

    protected function setUp(): void
    {
        $this->currency = new Currency('USD');
        $this->viewTransformer = new ViewTransformer($this->currency);
    }

    /**
     * @dataProvider reverseTransformDataProvider
     */
    public function testReverseTransform(?float $value, int $expected): void
    {
        $expectedResult = new Money($expected, $this->currency);
        $result = $this->viewTransformer->reverseTransform($value);

        self::assertTrue($result->equals($expectedResult));
    }

    /**
     * @param Money|string|null $money
     * @param int|float $expected
     *
     * @dataProvider transformDataProvider
     */
    public function testTransformsMoneyObjectToFloat($money, $expected): void
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
     * @return iterable<array<Money|string|float|null>>
     */
    public function transformDataProvider(): iterable
    {
        yield [null, 0.0];
        yield ['something else', 0.0];
        yield [1.0, 0.0];
        yield [new Money(1500, new Currency('USD')), 15.0];
        yield [new Money(1000, new Currency('USD')), 10.0];
        yield [new Money(100, new Currency('USD')), 1.0];
        yield [new Money(10, new Currency('USD')), 0.10];
        yield [new Money(1, new Currency('USD')), 0.01];
        yield [new Money(0, new Currency('USD')), 0.0];
    }
}
