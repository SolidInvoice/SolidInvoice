<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Tests\Serializer\Normalizer;

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\MoneyNormalizer;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizerTest extends TestCase
{
    public function testSupportsNormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize($object, $format = null, array $context = [])
            {
                return $object;
            }

            public function supportsNormalization($data, $format = null)
            {
                return true;
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
                return true;
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
                return $data;
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        static::assertTrue($normalizer->supportsNormalization(new Money(100, $currency)));
        static::assertFalse($normalizer->supportsNormalization(Money::class));
    }

    public function testSupportsDenormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize($object, $format = null, array $context = [])
            {
                return $object;
            }

            public function supportsNormalization($data, $format = null)
            {
                return true;
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
                return true;
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
                return $data;
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        static::assertTrue($normalizer->supportsDenormalization(null, Money::class));
        static::assertFalse($normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    public function testNormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize($object, $format = null, array $context = [])
            {
                return $object;
            }

            public function supportsNormalization($data, $format = null)
            {
                return true;
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
                return true;
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
                return $data;
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $money = new Money(10000, $currency);

        static::assertEquals('$100.00', $normalizer->normalize($money));
    }

    public function testDenormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize($object, $format = null, array $context = [])
            {
                return $object;
            }

            public function supportsNormalization($data, $format = null)
            {
                return true;
            }

            public function supportsDenormalization($data, $type, $format = null)
            {
                return true;
            }

            public function denormalize($data, $class, $format = null, array $context = [])
            {
                return $data;
            }
        };

        $currency = new Currency('USD');
        $normalizer = new MoneyNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $money = new Money(10000, $currency);

        static::assertEquals($money, $normalizer->denormalize(100, Money::class));
    }
}
