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

use DateMalformedStringException;
use DateTimeImmutable;
use SolidInvoice\CoreBundle\Entity\BillingPeriod;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
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
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): BillingPeriod
    {
        if (! is_array($data)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('The type of the "period" attribute must be array, "%s" given.', get_debug_type($data)),
                $data,
                ['array'],
                $context['deserialization_path'] ?? 'period',
                true,
            );
        }

        return BillingPeriod::of(
            $this->parseDate($data['start'] ?? null, $context, 'start'),
            $this->parseDate($data['end'] ?? null, $context, 'end'),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function parseDate(mixed $value, array $context, string $field): ?DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }

        if (! is_string($value)) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('The type of the "period.%s" attribute must be string, "%s" given.', $field, get_debug_type($value)),
                $value,
                ['string'],
                ($context['deserialization_path'] ?? 'period') . '.' . $field,
                true,
            );
        }

        try {
            return new DateTimeImmutable($value);
        } catch (DateMalformedStringException $e) {
            throw NotNormalizableValueException::createForUnexpectedDataType(
                sprintf('The period "%s" date "%s" is not a valid date.', $field, $value),
                $value,
                ['string'],
                ($context['deserialization_path'] ?? 'period') . '.' . $field,
                true,
                0,
                $e,
            );
        }
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
