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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\AdditionalContactDetailsNormalizer;
use SolidInvoice\ClientBundle\Entity\AdditionalContactDetail;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AdditionalContactDetailsNormalizerTest extends TestCase
{
    use MockeryPHPUnitIntegration,
        DoctrineTestTrait;

    public function testSupportsNormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
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

        $normalizer = new AdditionalContactDetailsNormalizer($this->registry, $parentNormalizer);

        $this->assertTrue($normalizer->supportsNormalization(new AdditionalContactDetail()));
        $this->assertFalse($normalizer->supportsNormalization(AdditionalContactDetail::class));
    }

    public function testSupportsDenormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
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

        $normalizer = new AdditionalContactDetailsNormalizer($this->registry, $parentNormalizer);

        $this->assertTrue($normalizer->supportsDenormalization(null, AdditionalContactDetail::class));
        $this->assertFalse($normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    public function testNormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
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

        $normalizer = new AdditionalContactDetailsNormalizer($this->registry, $parentNormalizer);

        $additionalContactDetail = new AdditionalContactDetail();
        $type = new ContactType();
        $type->setName('email');
        $additionalContactDetail->setType($type)
            ->setValue('one@two.com');

        $this->assertSame(['type' => 'email', 'value' => 'one@two.com'], $normalizer->normalize($additionalContactDetail));
    }

    public function testDenormalization()
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
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
                $additionalContactDetail = new AdditionalContactDetail();
                $type = new ContactType();
                $type->setName($data['type']['name']);
                $additionalContactDetail->setType($type)
                    ->setValue($data['value']);

                return $additionalContactDetail;
            }
        };

        $normalizer = new AdditionalContactDetailsNormalizer($this->registry, $parentNormalizer);

        $additionalContactDetail = new AdditionalContactDetail();
        $additionalContactDetail->setType(new ContactType())
            ->setValue('one@two.com');

        $detail = $normalizer->denormalize(['type' => 'email', 'value' => 'one@two.com'], AdditionalContactDetail::class);
        $this->assertInstanceOf(AdditionalContactDetail::class, $detail);
        $this->assertSame('email', $detail->getType()->getName());
        $this->assertSame('one@two.com', $detail->getValue());
    }
}
