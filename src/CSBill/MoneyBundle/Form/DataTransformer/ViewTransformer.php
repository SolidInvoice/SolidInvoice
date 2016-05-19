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

class ViewTransformer implements DataTransformerInterface
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
        if ($value instanceof Money) {
            return $value->getAmount() / 100;
        }

        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            $value = 0;
        }

        return new Money((int) ($value * 100), $this->currency);
    }
}
