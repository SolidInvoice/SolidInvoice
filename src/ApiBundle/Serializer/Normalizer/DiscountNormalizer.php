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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use SolidInvoice\CoreBundle\Entity\Discount;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\DiscountNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final class DiscountNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param array{type: string|null, value: string|int|null} $data
     * @throws MathException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Discount
    {
        $discount = new Discount();
        $discount->setType($data['type'] ?? null);
        $discount->setValue($data['value'] ?? null);

        return $discount;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Discount::class === $type;
    }

    /**
     * @param Discount $object
     * @param array<string, mixed> $context
     * @return array{type: string, value: BigInteger|int|float|string|null}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'type' => $object->getType(),
            'value' => $object->getValue(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Discount;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Discount::class => true,
        ];
    }
}
