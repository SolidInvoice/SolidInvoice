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

namespace CSBill\MoneyBundle\Form\DataTransformer;

use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class ViewTransformer implements DataTransformerInterface
{
    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param Currency|string $currency
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($currency)
    {
        if (is_string($currency)) {
            $this->currency = new Currency($currency);
        } elseif ($currency instanceof Currency) {
            $this->currency = $currency;
        } else {
            throw new \InvalidArgumentException(
                sprintf(__METHOD__.' expects a Currency object or string, %s given', is_object($currency) ? get_class($currency) : gettype($currency))
            );
        }
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

        return new Money(((int) $value * 100), $this->currency);
    }
}
