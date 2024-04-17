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
use Symfony\Component\Serializer\Normalizer\ContextAwareDenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use function in_array;
use function is_a;
use function is_array;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Api\BillingUserNormalizerTest
 */
final class BillingUserNormalizer implements ContextAwareDenormalizerInterface, DenormalizerAwareInterface, ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    public function __construct(
        private readonly IriConverterInterface $iriConverter
    ) {
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->denormalizer->denormalize($data, $class, $format, $context + [self::class => true]);
    }

    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
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

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return is_array($data) && isset($context['resource_class'], $data['users']) && (
            $context['resource_class'] === Invoice::class ||
            $context['resource_class'] === RecurringInvoice::class ||
            $context['resource_class'] === Quote::class
        ) && is_array($data['users']) && ! isset($context[self::class]);
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $users = $object['users'];

        foreach ($users as $i => $user) {
            $object['users'][$i] = $this->iriConverter->getIriFromResource($user);
        }

        return $this->normalizer->normalize($object, $format, $context + [self::class => true]);
    }
}
