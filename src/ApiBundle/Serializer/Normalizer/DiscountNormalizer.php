<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Serializer\Normalizer;

use SolidInvoice\CoreBundle\Entity\Discount;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use Money\Currency;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DiscountNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @var MoneyFormatter
     */
    private $formatter;

    /**
     * @var NormalizerInterface
     */
    private $normalizer;

    /**
     * @var Currency
     */
    private $currency;

    public function __construct(NormalizerInterface $normalizer, MoneyFormatter $formatter, Currency $currency)
    {
        if (!$normalizer instanceof DenormalizerInterface) {
            throw new \InvalidArgumentException('The normalizer must implement '.DenormalizerInterface::class);
        }

        $this->formatter = $formatter;
        $this->normalizer = $normalizer;
        $this->currency = $currency;
    }

    public function denormalize($data, $class, $format = null, array $context = [])
    {
        $discount = new Discount();
        $discount->setType($data['type'] ?? null);
        $discount->setValue($data['value'] ?? null);

        return $discount;
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return Discount::class === $type;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        /* @var Discount $object */
        return [
            'type' => $object->getType(),
            'value' => $object->getValue(),
        ];
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && Discount::class === get_class($data);
    }
}
