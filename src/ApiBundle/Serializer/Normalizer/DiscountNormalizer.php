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
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use SolidInvoice\ApiBundle\Versioning\ApiVersion;
use SolidInvoice\CoreBundle\Entity\Discount;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\DiscountNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final class DiscountNormalizer implements NormalizerInterface, DenormalizerInterface, NormalizerAwareInterface, DenormalizerAwareInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    /**
     * @param array{type: string|null, value?: string|int|null} $data
     * @throws MathException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Discount
    {
        $discount = new Discount();
        $discount->setType($data['type'] ?? null);

        $value = $data['value'] ?? null;
        if (null !== $value) {
            // Decimal money is in major units of the document's currency, like every other amount.
            if (($context[ApiVersion::DECIMAL_MONEY] ?? false) && $discount->getType() === Discount::TYPE_MONEY) {
                $value = $this->denormalizer->denormalize($value, BigNumber::class, $format, $context);
            }

            $discount->setValue($value);
        }

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
        if (($context[ApiVersion::DECIMAL_MONEY] ?? false) && $object->getType() === Discount::TYPE_MONEY) {
            return [
                'type' => $object->getType(),
                'value' => $this->normalizer->normalize($object->getValueMoney(), $format, $context),
            ];
        }

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
