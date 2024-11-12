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

namespace SolidInvoice\ApiBundle\Serializer\Normalizer;

use SolidInvoice\ClientBundle\Entity\Credit;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\CreditNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final class CreditNormalizer implements NormalizerAwareInterface, NormalizerInterface, DenormalizerAwareInterface, DenormalizerInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if ($type === Credit::class) {
            return (new Credit())->setValue($data);
        }

        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Credit::class === $type;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): float
    {
        /** @var Credit $object */
        return $this->normalizer->normalize($object->getValue(), $format, $context);
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Credit;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Credit::class => true,
        ];
    }
}
