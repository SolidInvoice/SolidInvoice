<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2020
 */

namespace SolidInvoice\CoreBundle\Form\Transformer;

use Money\Money;
use Symfony\Component\Form\DataTransformerInterface;

class DiscountTransformer implements DataTransformerInterface
{
    public function transform($value): ?int
    {
        if (!$value instanceof Money) {
            return null !== $value ? (int) $value : $value;
        }

        return ((int) $value->getAmount()) / 100;
    }

    public function reverseTransform($value): string
    {
        return $value;
    }
}
