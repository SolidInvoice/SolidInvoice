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

use ArrayObject;
use Brick\Math\Exception\MathException;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ApiBundle\Serializer\Normalizer\CreditNormalizer;
use SolidInvoice\ClientBundle\Entity\Credit;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CreditNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
            {
                return $object;
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
            {
                return $data;
            }

            public function getSupportedTypes(?string $format): array
            {
                return [];
            }
        };

        $normalizer = new CreditNormalizer();
        $normalizer->setNormalizer($parentNormalizer);

        self::assertTrue($normalizer->supportsNormalization(new Credit()));
        self::assertFalse($normalizer->supportsNormalization(Credit::class));
    }

    public function testSupportsDenormalization(): void
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
            {
                return $object;
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
            {
                return $data;
            }

            public function getSupportedTypes(?string $format): array
            {
                return [];
            }
        };

        $normalizer = new CreditNormalizer();
        $normalizer->setDenormalizer($parentNormalizer);

        self::assertTrue($normalizer->supportsDenormalization(null, Credit::class));
        self::assertFalse($normalizer->supportsDenormalization([], NormalizerInterface::class));
    }

    /**
     * @throws MathException
     * @throws ExceptionInterface
     */
    public function testNormalization(): void
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
            {
                return $object->toFloat();
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
            {
                return $data;
            }

            public function getSupportedTypes(?string $format): array
            {
                return [];
            }
        };

        $normalizer = new CreditNormalizer();
        $normalizer->setNormalizer($parentNormalizer);

        $credit = new Credit();
        $credit->setValue(10000);

        self::assertEquals(10000, $normalizer->normalize($credit));
    }

    public function testDenormalization(): void
    {
        $parentNormalizer = new class() implements NormalizerInterface, DenormalizerInterface {
            public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
            {
                return $object;
            }

            public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
            {
                return true;
            }

            public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
            {
                return 123;
            }

            public function getSupportedTypes(?string $format): array
            {
                return [];
            }
        };

        $normalizer = new CreditNormalizer();
        $normalizer->setDenormalizer($parentNormalizer);

        self::assertEquals((new Credit())->setValue(123), $normalizer->denormalize(123, Credit::class));
    }
}
