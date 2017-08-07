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

use CSBill\ApiBundle\Serializer\Normalizer\CreditNormalizer;
use CSBill\ClientBundle\Entity\Credit;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CreditNormalizerTest extends TestCase
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

        $normalizer = new CreditNormalizer($parentNormalizer);

        $this->assertTrue($normalizer->supportsNormalization(new Credit()));
        $this->assertFalse($normalizer->supportsNormalization(Credit::class));
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

        $normalizer = new CreditNormalizer($parentNormalizer);

        $this->assertTrue($normalizer->supportsDenormalization(null, Credit::class));
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

        $normalizer = new CreditNormalizer($parentNormalizer);

        $credit = new Credit();
        $money = new Money(10000, new Currency('USD'));
        $credit->setValue($money);

        $this->assertEquals($money, $normalizer->normalize($credit));
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
                return 123;
            }
        };

        $normalizer = new CreditNormalizer($parentNormalizer);

        $this->assertEquals(123, $normalizer->denormalize([], Credit::class));
    }
}
