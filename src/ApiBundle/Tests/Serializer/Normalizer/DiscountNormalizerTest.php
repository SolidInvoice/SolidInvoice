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

namespace SolidInvoice\ApiBundle\Tests\Serializer\Normalizer;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\DiscountNormalizer;
use SolidInvoice\CoreBundle\Entity\Discount;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DiscountNormalizerTest extends TestCase
{
    private DiscountNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new DiscountNormalizer();
    }

    public function testSupportsNormalization(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new Discount()));
        self::assertFalse($this->normalizer->supportsNormalization(Discount::class));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(null, Discount::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    /**
     * @throws MathException
     */
    public function testNormalization(): void
    {
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(100);

        self::assertEquals(['type' => 'money', 'value' => BigInteger::of(100)], $this->normalizer->normalize($discount));
    }

    public function testDenormalization(): void
    {
        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(10000);

        self::assertEquals($discount, $this->normalizer->denormalize(['type' => 'money', 'value' => 10000], Discount::class));
    }
}
