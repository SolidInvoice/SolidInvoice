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

namespace SolidInvoice\MoneyBundle\Serializer;

use ArrayObject;
use InvalidArgumentException;
use Money\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class CurrencySerializer implements NormalizerInterface, DenormalizerInterface
{
    private DenormalizerInterface | NormalizerInterface $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        if (! $normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The normalizer must implement ' . DenormalizerInterface::class);
        }

        $this->normalizer = $normalizer;
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): Currency
    {
        if ($type === Currency::class && is_string($data)) {
            return new Currency($data);
        }

        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return Currency::class === $type;
    }

    public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|ArrayObject|null
    {
        return $object;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Currency;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Currency::class => true,
        ];
    }
}
