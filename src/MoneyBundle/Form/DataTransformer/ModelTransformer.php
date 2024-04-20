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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class ModelTransformer implements DataTransformerInterface
{
    private ?Currency $currency = null;

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
            throw new InvalidArgumentException(sprintf(__METHOD__ . ' expects a Currency object or string, %s given', get_debug_type($currency)));
        }
    }

    /**
     * @throws MathException
     */
    public function transform($value)
    {
        if (null === $value) {
            $value = 0;
        }

        if ($value instanceof Money) {
            return $value;
        }

        return new Money(BigInteger::of($value)->toInt(), $this->currency);
    }

    /**
     * @throws MathException
     */
    public function reverseTransform($value)
    {
        if ($value instanceof Money) {
            return BigInteger::of($value->getAmount());
        }

        return BigInteger::of($value->getAmount());
    }
}
