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
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\AdditionalContactDetailsNormalizerTest
 */
class AdditionalContactDetailsNormalizer implements NormalizerInterface, DenormalizerInterface
{
    private readonly DenormalizerInterface|NormalizerInterface $normalizer;

    public function __construct(private readonly ManagerRegistry $registry, NormalizerInterface $normalizer)
    {
        if (! $normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The normalizer must implement ' . DenormalizerInterface::class);
        }

        $this->normalizer = $normalizer;
    }

    public function denormalize($data, $type, $format = null, array $context = [])
    {
        $data['type'] = [
            'name' => $data['type'],
        ];

        /** @var AdditionalContactDetail $detail */
        $detail = $this->normalizer->denormalize($data, $type, $format, $context);
        $repository = $this->registry->getRepository(ContactType::class);
        $detail->setType($repository->findOneBy(['name' => $detail->getType()->getName()]));

        return $detail;
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return AdditionalContactDetail::class === $type;
    }

    /**
     * @param AdditionalContactDetail $object
     * @param string|null $format
     * @param array<string, mixed> $context
     * @return array<string, string>
     */
    public function normalize($object, $format = null, array $context = []): array
    {
        return ['type' => $object->getType()->getName(), 'value' => $object->getValue()];
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof AdditionalContactDetail;
    }
}
