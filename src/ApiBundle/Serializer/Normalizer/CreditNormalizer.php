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

use InvalidArgumentException;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Credit;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @see \SolidInvoice\ApiBundle\Tests\Serializer\Normalizer\CreditNormalizerTest
 */
class CreditNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var NormalizerInterface|DenormalizerInterface
     */
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        if (! $normalizer instanceof DenormalizerInterface) {
            throw new InvalidArgumentException('The normalizer must implement ' . DenormalizerInterface::class);
        }

        $this->normalizer = $normalizer;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null): bool
    {
        return Credit::class === $type;
    }

    public function normalize($object, $format = null, array $context = []): object
    {
        /** @var Credit $object */
        return $object->getValue();
    }

    public function supportsNormalization($data, $format = null): bool
    {
        return $data instanceof Credit;
    }
}
