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

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Money\Money;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var MoneyFormatter
     */
    private $formatter;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    public function __construct(NormalizerInterface $normalizer, MoneyFormatter $formatter)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('The normalizer must implement '.DenormalizerInterface::class);
        }

        $this->formatter = $formatter;
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
        /* @var Money $object */
        return $this->formatter->format($object);
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && Money::class === get_class($data);
    }
}
