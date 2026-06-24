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
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Form\Transformer\DiscountTransformer;

final class DiscountTransformerTest extends TestCase
{
    private DiscountTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DiscountTransformer();
    }

    public function testTransformNullReturnsZero(): void
    {
        self::assertSame(0.0, $this->transformer->transform(null));
    }

    public function testTransformConvertsToPercentage(): void
    {
        $result = $this->transformer->transform(BigDecimal::of(5000));
        self::assertSame(50.0, $result);
    }

    public function testReverseTransformEmptyStringReturnsZero(): void
    {
        $result = $this->transformer->reverseTransform('');
        self::assertSame('0', (string) $result);
    }

    public function testReverseTransformNullReturnsZero(): void
    {
        $result = $this->transformer->reverseTransform(null);
        self::assertSame('0', (string) $result);
    }

    public function testReverseTransformConvertsToStorageValue(): void
    {
        $result = $this->transformer->reverseTransform('50');
        self::assertInstanceOf(BigNumber::class, $result);
        self::assertSame('5000', (string) $result);
    }

    public function testReverseTransformHandlesFloat(): void
    {
        $result = $this->transformer->reverseTransform(10.5);
        self::assertSame('1050', (string) $result);
    }
}
