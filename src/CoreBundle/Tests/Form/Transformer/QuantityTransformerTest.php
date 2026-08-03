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
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Locale;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Doctrine\Type\QuantityType;
use SolidInvoice\CoreBundle\Form\Transformer\QuantityTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;

final class QuantityTransformerTest extends TestCase
{
    private QuantityTransformer $transformer;

    private string $locale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locale = Locale::getDefault();
        Locale::setDefault('en');
        $this->transformer = new QuantityTransformer();
    }

    protected function tearDown(): void
    {
        Locale::setDefault($this->locale);
        parent::tearDown();
    }

    /**
     * @return iterable<string, array{BigNumber, string}>
     */
    public static function modelValues(): iterable
    {
        yield 'integer' => [BigInteger::of(2), '2'];
        yield 'one decimal' => [BigDecimal::of('2.5'), '2.5'];
        yield 'trailing zeros are not padded' => [BigDecimal::of('2.500000'), '2.5'];
        yield 'full scale' => [BigDecimal::of('0.000001'), '0.000001'];
        yield 'negative' => [BigDecimal::of('-1.25'), '-1.25'];
        yield 'zero' => [BigDecimal::zero(), '0'];
    }

    #[DataProvider('modelValues')]
    public function testTransform(BigNumber $value, string $expected): void
    {
        self::assertSame($expected, $this->transformer->transform($value));
    }

    public function testTransformsNullToAnEmptyString(): void
    {
        self::assertSame('', $this->transformer->transform(null));
    }

    public function testTransformUsesTheLocaleDecimalSeparator(): void
    {
        Locale::setDefault('de');

        self::assertSame('2,5', new QuantityTransformer()->transform(BigDecimal::of('2.5')));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function viewValues(): iterable
    {
        yield 'integer' => ['2', '2'];
        yield 'decimal point' => ['2.5', '2.5'];
        yield 'decimal comma' => ['2,5', '2.5'];
        yield 'full scale' => ['0.000001', '0.000001'];
        // Parsed verbatim; setQty() canonicalises, and the field re-renders from the model.
        yield 'padded' => ['2.500000', '2.500000'];
        yield 'negative' => ['-1.25', '-1.25'];
        yield 'surrounding whitespace' => ["  2.5\n", '2.5'];
        yield 'non-breaking space' => ["2\xc2\xa0500.5", '2500.5'];
    }

    #[DataProvider('viewValues')]
    public function testReverseTransform(string $value, string $expected): void
    {
        $result = $this->transformer->reverseTransform($value);

        self::assertInstanceOf(BigDecimal::class, $result);
        self::assertSame($expected, (string) $result);
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function emptyViewValues(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'whitespace only' => ['   '];
    }

    #[DataProvider('emptyViewValues')]
    public function testReverseTransformsEmptyValuesToNull(?string $value): void
    {
        self::assertNull($this->transformer->reverseTransform($value));
    }

    /**
     * Beyond-scale input is passed through untouched: setQty() owns the rounding, so that a
     * form and an API call resolve the same quantity identically.
     */
    public function testReverseTransformLeavesRoundingToTheEntity(): void
    {
        $result = $this->transformer->reverseTransform('0.0000005');

        self::assertInstanceOf(BigDecimal::class, $result);
        self::assertSame('0.0000005', (string) $result);
        self::assertSame('0', (string) QuantityType::normalize($result));
    }

    public function testReverseTransformRejectsNonNumericInput(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer->reverseTransform('one and a half');
    }

    public function testReverseTransformRejectsNonStrings(): void
    {
        $this->expectException(TransformationFailedException::class);

        $this->transformer->reverseTransform(2.5);
    }

    #[DataProvider('modelValues')]
    public function testRoundTripsWithoutLosingDigits(BigNumber $value, string $view): void
    {
        $result = $this->transformer->reverseTransform($view);

        self::assertInstanceOf(BigDecimal::class, $result);
        self::assertTrue($value->isEqualTo($result), sprintf('Expected %s, got %s', $value, $result));
    }
}
