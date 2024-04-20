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

use Brick\Math\BigNumber;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\NumberFormatException;
use InvalidArgumentException;
use Money\Currency;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class ViewTransformer implements DataTransformerInterface
{
    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly Currency $currency
    ) {
    }

    public function transform($value): float
    {
        if ($value instanceof Money) {
            return (float) ($value->getAmount() / 100);
        }

        return 0.0;
    }

    /**
     * @throws NumberFormatException
     * @throws DivisionByZeroException
     */
    public function reverseTransform($value): Money
    {
        if (! is_numeric($value)) {
            $value = 0;
        }

        return new Money(BigNumber::of($value)->multipliedBy(100)->toInt(), $this->currency);
    }
}
