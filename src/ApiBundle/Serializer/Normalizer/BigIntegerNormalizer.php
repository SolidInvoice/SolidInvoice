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

use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function is_a;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\BigIntegerNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final class BigIntegerNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @throws MathException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): BigNumber
    {
        if ($context['api_denormalize'] ?? false) {
            return BigNumber::of($data)->toBigDecimal()->multipliedBy(100);
        }

        return BigNumber::of($data);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_a($type, BigNumber::class, true);
    }

    /**
     * @param BigNumber $object
     * @param array<string, mixed> $context
     * @throws MathException
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): float
    {
        if (isset($context['api_attribute'])) {
            return $object->toBigDecimal()->dividedBy(100, 2, RoundingMode::HALF_EVEN)->toFloat();
        }

        return $object->toBigDecimal()->toFloat();
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BigNumber;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BigNumber::class => true,
        ];
    }
}
