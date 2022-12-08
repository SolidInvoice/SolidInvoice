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

namespace SolidInvoice\MoneyBundle\Form\DataTransformer;

use InvalidArgumentException;
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
     * @throws InvalidArgumentException
     */
    public function __construct($currency)
    {
        if (is_string($currency)) {
            $this->currency = new Currency($currency);
        } elseif ($currency instanceof Currency) {
            $this->currency = $currency;
        } else {
            throw new InvalidArgumentException(sprintf(__METHOD__ . ' expects a Currency object or string, %s given', is_object($currency) ? get_class($currency) : gettype($currency)));
        }
    }

    public function transform($value)
    {
        if ($value instanceof Money) {
            return $value->getAmount() / 100;
        }

        return 0;
    }

    public function reverseTransform($value)
    {
        if (null === $value) {
            $value = 0;
        }

        return new Money(((int) $value * 100), $this->currency);
    }
}
