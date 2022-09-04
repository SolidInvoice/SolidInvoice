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

use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\DiscountNormalizer;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\MoneyBundle\Entity\Money;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DiscountNormalizerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Money::setBaseCurrency('USD');
    }

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
        $normalizer = new DiscountNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        static::assertTrue($normalizer->supportsNormalization(new Discount()));
        static::assertFalse($normalizer->supportsNormalization(Discount::class));
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
        $normalizer = new DiscountNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        static::assertTrue($normalizer->supportsDenormalization(null, Discount::class));
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
        $normalizer = new DiscountNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(100);

        static::assertSame(['type' => 'money', 'value' => new \Money\Money(10000, $currency)], $normalizer->normalize($discount));
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
        $normalizer = new DiscountNormalizer($parentNormalizer, new MoneyFormatter('en', $currency), $currency);

        $discount = new Discount();
        $discount->setType(Discount::TYPE_MONEY);
        $discount->setValue(10000);

        static::assertEquals($discount, $normalizer->denormalize(['type' => 'money', 'value' => 10000], Discount::class));
    }
}
