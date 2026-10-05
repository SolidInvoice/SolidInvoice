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

namespace SolidInvoice\CoreBundle\Tests\Form\Transformer;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Form\Transformer\DiscountTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class DiscountTransformerTest extends TestCase
{
    private DiscountTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new DiscountTransformer();
    }

    /**
     * @return iterable<string, array{BigNumber|null, float}>
     */
    public static function transformValues(): iterable
    {
        yield 'null returns zero' => [null, 0.0];
        yield 'BigDecimal 5000 (stored 50%) returns 50.0' => [BigDecimal::of('5000'), 50.0];
        yield 'BigDecimal 50 (stored 0.5%) returns 0.5' => [BigDecimal::of('50'), 0.5];
        yield 'BigDecimal 0 returns 0.0' => [BigDecimal::of('0'), 0.0];
    }

    /**
     * @param BigNumber|null $value
     */
    #[DataProvider('transformValues')]
    public function testTransform(mixed $value, float $expected): void
    {
        self::assertSame($expected, $this->transformer->transform($value));
    }

    /**
     * @return iterable<string, array{string|float|null, string}>
     */
    public static function reverseTransformValues(): iterable
    {
        yield 'null returns zero' => [null, '0'];
        yield 'empty string returns zero' => ['', '0'];
        yield '50 returns 5000' => ['50', '5000'];
        yield '0 returns 0' => ['0', '0'];
        yield '100 returns 10000' => ['100', '10000'];
        yield '25.5 returns 2550.0' => ['25.5', '2550.0'];
    }

    #[DataProvider('reverseTransformValues')]
    public function testReverseTransform(mixed $value, string $expected): void
    {
        $result = $this->transformer->reverseTransform($value);

        self::assertInstanceOf(BigNumber::class, $result);
        self::assertTrue(
            BigDecimal::of($expected)->isEqualTo($result),
            sprintf('Expected %s, got %s', $expected, $result)
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidValues(): iterable
    {
        yield 'URL' => ['https://tr.ee/TZLvmL'];
        yield 'plain text' => ['one hundred'];
        yield 'alphanumeric' => ['50abc'];
        yield 'whitespace only' => ['   '];
    }

    #[DataProvider('invalidValues')]
    public function testReverseTransformRejectsNonNumericInput(string $value): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer->reverseTransform($value);
    }
}
