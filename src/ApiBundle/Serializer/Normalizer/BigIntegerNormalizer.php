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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function is_a;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\BigIntegerNormalizerTest
 */
class BigIntegerNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @throws MathException
     */
    public function denormalize($data, string $type, string $format = null, array $context = []): BigNumber
    {
        return BigNumber::of($data)->toBigDecimal()->multipliedBy(100)->toBigInteger();
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return is_a($type, BigNumber::class, true);
    }

    /**
     * @param BigNumber $object
     * @param array<string, mixed> $context
     * @throws MathException
     */
    public function normalize($object, string $format = null, array $context = []): float
    {
        return $object->toBigDecimal()->dividedBy(100, 2, RoundingMode::HALF_EVEN)->toFloat();
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof BigNumber;
    }
}
