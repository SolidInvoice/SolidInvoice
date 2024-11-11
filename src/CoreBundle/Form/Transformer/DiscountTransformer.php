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

namespace SolidInvoice\CoreBundle\Form\Transformer;

use Brick\Math\BigNumber;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Math\RoundingMode;
use Symfony\Component\Form\DataTransformerInterface;

class DiscountTransformer implements DataTransformerInterface
{
    /**
     * @throws DivisionByZeroException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function transform(mixed $value): float
    {
        if ($value === null) {
            return 0.0;
        }

        return BigNumber::of($value)->toBigDecimal()->dividedBy(100, 2, RoundingMode::HALF_EVEN)->toFloat();
    }

    /**
     * @throws DivisionByZeroException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function reverseTransform(mixed $value): BigNumber
    {
        return BigNumber::of($value)
            ->toBigDecimal()
            ->multipliedBy(100)
        ;
    }
}
