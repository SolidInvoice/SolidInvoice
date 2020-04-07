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

use SolidInvoice\ApiBundle\Serializer\Normalizer\DiscountNormalizer;
use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\MoneyBundle\Entity\Money;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DiscountNormalizerTest extends TestCase
{
    protected function setUp()
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

        $this->assertTrue($normalizer->supportsNormalization(new Discount()));
        $this->assertFalse($normalizer->supportsNormalization(Discount::class));
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

        $this->assertTrue($normalizer->supportsDenormalization(null, Discount::class));
        $this->assertFalse($normalizer->supportsDenormalization([], NormalizerInterface::class));
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

        $this->assertEquals(['type' => 'money', 'value' => new \Money\Money(10000, $currency)], $normalizer->normalize($discount));
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

        $this->assertEquals($discount, $normalizer->denormalize(['type' => 'money', 'value' => 10000], Discount::class));
    }
}
