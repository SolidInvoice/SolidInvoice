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
use function sprintf;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\BigIntegerNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final class BigIntegerNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * Context flag marking whether the {@see BigNumber} being (de)normalized is a monetary
     * amount held in the minor unit, and therefore needs scaling by 100 across the API
     * boundary. Defaults to true, since most of them are.
     *
     * Set it to false with {@see \Symfony\Component\Serializer\Attribute\Context} on any
     * `BigNumber` property that is a plain number — a line quantity, for instance, which
     * is exposed exactly as it is stored.
     */
    public const string MONETARY = 'monetary_amount';

    /**
     * @throws MathException
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): BigNumber
    {
        // JSON numbers arrive as floats. `(string)` would serialise them at PHP's `precision`
        // ini setting, so the same payload could denormalize differently from one host to
        // the next; %.14G pins that to the historical default and makes it deterministic.
        $data = is_float($data) ? sprintf('%.14G', $data) : $data;

        if (($context['api_denormalize'] ?? false) && ($context[self::MONETARY] ?? true)) {
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
        if (isset($context['api_attribute']) && ($context[self::MONETARY] ?? true)) {
            return $object->toBigDecimal()->dividedBy(100, 2, RoundingMode::HalfEven)->toFloat();
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
