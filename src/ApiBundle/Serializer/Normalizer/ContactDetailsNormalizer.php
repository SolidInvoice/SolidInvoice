<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ApiBundle\Serializer\Normalizer;

use CSBill\ClientBundle\Entity\AdditionalContactDetail;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ContactDetailsNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('The normalizer must implement '.DenormalizerInterface::class);
        }

        $this->normalizer = $normalizer;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return $this->normalizer->denormalize($data, $class, $format, $context);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return $this->normalizer->supportsDenormalization($data, $type, $format);
    }

    public function normalize($object, $format = null, array $context = [])
    {
        /* @var AdditionalContactDetail $object */
        return ['type' => $object->getType()->getName(), 'value' => $object->getValue()];
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && AdditionalContactDetail::class === get_class($data);
    }
}
