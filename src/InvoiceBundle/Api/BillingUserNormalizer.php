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

namespace SolidInvoice\InvoiceBundle\Api;

use ApiPlatform\Api\IriConverterInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use function in_array;
use function is_a;
use function is_array;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Api\BillingUserNormalizerTest
 */
final class BillingUserNormalizer implements DenormalizerAwareInterface, DenormalizerInterface, NormalizerAwareInterface, NormalizerInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    public function __construct(
        private readonly IriConverterInterface $iriConverter
    ) {
    }

    /**
     * @param array<string, mixed> $context
     * @throws ExceptionInterface
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return $this->denormalizer->denormalize($data, $type, $format, $context + [self::class => true]);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return in_array($format, ['json', 'jsonld'], true) &&
            (
                is_a($type, Invoice::class, true) ||
                is_a($type, RecurringInvoice::class, true) ||
                is_a($type, Quote::class, true)
            ) &&
            ! empty($data['users']) &&
            ! isset($context[self::class]);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && isset($context['resource_class'], $data['users']) && (
            $context['resource_class'] === Invoice::class ||
                $context['resource_class'] === RecurringInvoice::class ||
                $context['resource_class'] === Quote::class
        ) && is_array($data['users']) && ! isset($context[self::class]);
    }

    /**
     * @param array<string, mixed> $context
     * @throws ExceptionInterface
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array | string | int | float | bool | \ArrayObject | null
    {
        $users = $object['users'];

        foreach ($users as $i => $user) {
            $object['users'][$i] = $this->iriConverter->getIriFromResource($user);
        }

        return $this->normalizer->normalize($object, $format, $context + [self::class => true]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Invoice::class => false,
            RecurringInvoice::class => false,
            Quote::class => false,
        ];
    }
}
