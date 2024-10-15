<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MoneyBundle\Serializer;

use InvalidArgumentException;
use Money\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class CurrencySerializer implements NormalizerInterface, DenormalizerInterface
{
    private readonly DenormalizerInterface | NormalizerInterface $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        if (! $normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The normalizer must implement ' . DenormalizerInterface::class);
        }

        $this->normalizer = $normalizer;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        if ($type === Currency::class && is_string($data)) {
            return new Currency($data);
        }

        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return Currency::class === $type;
    }

    public function normalize($object, $format = null, array $context = []): object
    {
        return $object;
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Currency;
    }
}
