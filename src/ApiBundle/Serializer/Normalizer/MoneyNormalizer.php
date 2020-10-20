<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Serializer\Normalizer;

use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use Money\Currency;
use Money\Money;
use SolidInvoice\MoneyBundle\Entity\Money as MoneyEntity;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
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

    /**
     * @var Currency
     */
    private $currency;

    public function __construct(NormalizerInterface $normalizer, MoneyFormatterInterface $formatter, Currency $currency)
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
        // @TODO: Currency should be determined if there is a client added to the context
        return new Money($data * 100, $this->currency);
    }

    public function supportsDenormalization($data, $type, $format = null)
    {
        return Money::class === $type || MoneyEntity::class === $type;
    }

    public function normalize($object, $format = null, array $context = [])
    {
        /* @var Money $object */
        return $this->formatter->format($object);
    }

    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $data instanceof Money;
    }
}
