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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class DiscountTransformer implements DataTransformerInterface
{
    /**
     * @throws MathException
     */
    public function transform($value): ?int
    {
        if ($value instanceof BigInteger) {
            return $value->toInt();
        }

        if (! $value instanceof Money) {
            return null !== $value ? (int) $value : $value;
        }

        return ((int) $value->getAmount()) / 100;
    }

    public function reverseTransform($value): string
    {
        return $value;
    }
}
