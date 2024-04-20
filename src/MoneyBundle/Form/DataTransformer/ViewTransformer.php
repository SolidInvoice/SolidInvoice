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
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
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

    /**
     * @throws DivisionByZeroException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function transform($value): float
    {
        if ($value instanceof Money) {
            return BigNumber::of($value->getAmount())->toBigDecimal()->dividedBy(100, 2)->toFloat();
        }

        return 0.0;
    }

    /**
     * @throws DivisionByZeroException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function reverseTransform($value): Money
    {
        if (! is_numeric($value)) {
            $value = 0;
        }

        return new Money(BigNumber::of($value)->toBigDecimal()->multipliedBy(100)->toInt(), $this->currency);
    }
}
