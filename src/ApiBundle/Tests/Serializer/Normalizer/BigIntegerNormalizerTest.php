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

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\BigIntegerNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class BigIntegerNormalizerTest extends TestCase
{
    private BigIntegerNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BigIntegerNormalizer();
    }

    /**
     * @throws MathException
     */
    public function testSupportsNormalization(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(BigInteger::of(1)));
        self::assertTrue($this->normalizer->supportsNormalization(BigDecimal::of(1.1)));
        self::assertTrue($this->normalizer->supportsNormalization(BigNumber::of(1.1)));
        self::assertFalse($this->normalizer->supportsNormalization(BigInteger::class));
    }

    public function testSupportsDenormalization(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization(null, BigInteger::class));
        self::assertTrue($this->normalizer->supportsDenormalization(null, BigDecimal::class));
        self::assertTrue($this->normalizer->supportsDenormalization(null, BigNumber::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    /**
     * @throws MathException
     */
    public function testNormalization(): void
    {
        self::assertEquals(1, $this->normalizer->normalize(BigInteger::of(100)));
    }

    /**
     * @throws MathException
     */
    public function testDenormalization(): void
    {
        self::assertEquals(BigInteger::of(1000000), $this->normalizer->denormalize(10000, BigNumber::class));
        self::assertEquals(BigInteger::of(1000010), $this->normalizer->denormalize(10000.1, BigNumber::class));
    }
}
