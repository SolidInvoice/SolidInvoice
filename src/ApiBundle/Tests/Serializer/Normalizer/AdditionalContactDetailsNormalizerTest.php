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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\AdditionalContactDetailsNormalizer;
use SolidInvoice\ClientBundle\Entity\AdditionalContactDetail;
use SolidInvoice\ClientBundle\Entity\ContactType;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @covers \SolidInvoice\ApiBundle\Serializer\Normalizer\AdditionalContactDetailsNormalizer
 */
final class AdditionalContactDetailsNormalizerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use DoctrineTestTrait;

    public function testSupportsNormalization(): void
    {
        $normalizer = new AdditionalContactDetailsNormalizer($this->registry);

        self::assertTrue($normalizer->supportsNormalization(new AdditionalContactDetail()));
        self::assertFalse($normalizer->supportsNormalization(AdditionalContactDetail::class));
    }

    public function testSupportsDenormalization(): void
    {
        $normalizer = new AdditionalContactDetailsNormalizer($this->registry);

        self::assertTrue($normalizer->supportsDenormalization(null, AdditionalContactDetail::class));
        self::assertFalse($normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    public function testNormalization(): void
    {
        $normalizer = new AdditionalContactDetailsNormalizer($this->registry);

        $additionalContactDetail = new AdditionalContactDetail();
        $type = new ContactType();
        $type->setName('email');
        $additionalContactDetail->setType($type)
            ->setValue('one@two.com');

        self::assertSame(['type' => 'email', 'value' => 'one@two.com'], $normalizer->normalize($additionalContactDetail));
    }

    public function testDenormalization(): void
    {
        $parentNormalizer = new class() implements DenormalizerInterface {
            /**
             * @param array<string, mixed> $context
             */
            public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            /**
             * @param array<string, mixed> $context
             */
            public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
            {
                $additionalContactDetail = new AdditionalContactDetail();
                $type = new ContactType();
                $type->setName($data['type']['name']);
                $additionalContactDetail->setType($type)
                    ->setValue($data['value']);

                return $additionalContactDetail;
            }

            public function getSupportedTypes(?string $format): array
            {
                return [
                    AdditionalContactDetail::class => true,
                ];
            }
        };

        $normalizer = new AdditionalContactDetailsNormalizer($this->registry);
        $normalizer->setDenormalizer($parentNormalizer);

        $additionalContactDetail = new AdditionalContactDetail();
        $additionalContactDetail->setType(new ContactType())
            ->setValue('one@two.com');

        $detail = $normalizer->denormalize(['type' => 'email', 'value' => 'one@two.com'], AdditionalContactDetail::class);
        self::assertSame('email', $detail->getType()->getName());
        self::assertSame('one@two.com', $detail->getValue());
    }
}
