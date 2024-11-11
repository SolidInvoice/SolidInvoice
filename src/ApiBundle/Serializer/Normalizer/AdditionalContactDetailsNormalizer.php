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

use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use SolidInvoice\ClientBundle\Entity\AdditionalContactDetail;
use SolidInvoice\ClientBundle\Entity\ContactType;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\AdditionalContactDetailsNormalizerTest
 */
#[AutoconfigureTag('serializer.normalizer')]
final class AdditionalContactDetailsNormalizer implements NormalizerAwareInterface, NormalizerInterface, DenormalizerAwareInterface, DenormalizerInterface
{
    use NormalizerAwareTrait;
    use DenormalizerAwareTrait;

    public function __construct(
        private readonly ManagerRegistry $registry,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): AdditionalContactDetail
    {
        if (! array_key_exists('type', $data) || ! array_key_exists('value', $data)) {
            throw new InvalidArgumentException('Invalid data');
        }

        $detail = new AdditionalContactDetail();
        $repository = $this->registry->getRepository(ContactType::class);
        $contactType = $repository->findOneBy(['name' => $data['type']]);

        if (! $contactType instanceof ContactType) {
            throw new InvalidArgumentException('Invalid contact type');
        }

        $detail->setType($contactType);
        $detail->setValue($data['value']);

        return $detail;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return AdditionalContactDetail::class === $type;
    }

    /**
     * @param AdditionalContactDetail $object
     * @param array<string, mixed> $context
     * @return array{type: string, value: string|null}
     */
    public function normalize(mixed $object, ?string $format = null, array $context = []): array
    {
        return [
            'type' => $object->getType()?->getName(),
            'value' => $object->getValue(),
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof AdditionalContactDetail;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            AdditionalContactDetail::class => null,
        ];
    }
}
