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

use DateTimeImmutable;
use SolidInvoice\CoreBundle\Entity\BillingPeriod;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Without this, API Platform's JSON-LD normalizer wraps the embeddable in a
 * genid id and a type entry naming the class, because it falls through to
 * the generic object normalizer. Claiming the type here first, the same way
 * {@see DiscountNormalizer} does for the other embeddable on an invoice,
 * keeps the wire shape to plain `start`/`end` keys.
 *
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\BillingPeriodNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final class BillingPeriodNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param array{start?: string|null, end?: string|null} $data
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): BillingPeriod
    {
        $start = $data['start'] ?? null;
        $end = $data['end'] ?? null;

        return BillingPeriod::of(
            null !== $start ? new DateTimeImmutable($start) : null,
            null !== $end ? new DateTimeImmutable($end) : null,
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return BillingPeriod::class === $type;
    }

    /**
     * @param BillingPeriod $object
     * @param array<string, mixed> $context
     * @return array{start: string|null, end: string|null}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'start' => $object->getStart()?->format(DateTimeImmutable::ATOM),
            'end' => $object->getEnd()?->format(DateTimeImmutable::ATOM),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof BillingPeriod;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            BillingPeriod::class => true,
        ];
    }
}
