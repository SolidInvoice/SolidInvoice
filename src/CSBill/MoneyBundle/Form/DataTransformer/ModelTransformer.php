<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Form\DataTransformer;

use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class ModelTransformer implements DataTransformerInterface
{
    /**
     * @var \Money\Currency
     */
    private $currency;

    /**
     * @param \Money\Currency $currency
     */
    public function __construct(Currency $currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            $value = 0;
        }

        if ($value instanceof Money) {
            return $value;
        }

        return new Money((int) ($value * 100), $this->currency);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if ($value instanceof Money) {
            return $value;
        }

        if (is_int($value)) {
            return new Money($value, $this->currency);
        }

        return 0;
    }
}
