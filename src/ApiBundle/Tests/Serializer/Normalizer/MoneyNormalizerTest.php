<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Tests\Serializer\Normalizer;

use CSBill\ApiBundle\Serializer\Normalizer\MoneyNormalizer;
use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizerTest extends TestCase
{
    public function testSupportsNormalization()
    {
        $parentNormalizer = new class implements NormalizerInterface, DenormalizerInterface
        {
            public function normalize($object, $format = null, array $context = [])
            {
            }

            public function supportsNormalization($data, $format = null)
            {
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $this->assertTrue($normalizer->supportsNormalization(new Money(100, $currency)));
        $this->assertFalse($normalizer->supportsNormalization(Money::class));
    }

    public function testSupportsDenormalization()
    {
        $parentNormalizer = new class implements NormalizerInterface, DenormalizerInterface
        {
            public function normalize($object, $format = null, array $context = [])
            {
            }

            public function supportsNormalization($data, $format = null)
            {
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $this->assertTrue($normalizer->supportsDenormalization(null, Money::class));
        $this->assertFalse($normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    public function testNormalization()
    {
        $parentNormalizer = new class implements NormalizerInterface, DenormalizerInterface
        {
            public function normalize($object, $format = null, array $context = [])
            {
            }

            public function supportsNormalization($data, $format = null)
            {
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $money = new Money(10000, $currency);

        $this->assertEquals('$100.00', $normalizer->normalize($money));
    }

    public function testDenormalization()
    {
        $parentNormalizer = new class implements NormalizerInterface, DenormalizerInterface
        {
            public function normalize($object, $format = null, array $context = [])
            {
            }

            public function supportsNormalization($data, $format = null)
            {
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $money = new Money(10000, $currency);

        $this->assertEquals($money, $normalizer->denormalize(100, Money::class));
    }
}
