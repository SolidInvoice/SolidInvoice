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

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\DivisionByZeroException;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Money\Currency;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @implements DataTransformerInterface<BigNumber, float>
 * @see \SolidInvoice\MoneyBundle\Tests\Form\DataTransformer\ViewTransformerTest
 */
final class ViewTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly Currency $currency,
        private readonly CurrencyScale $scale = new CurrencyScale(),
    ) {
    }

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

        return $this->scale->toMajorUnit($value, $this->currency);
    }

    /**
     * @throws DivisionByZeroException
     * @throws RoundingNecessaryException
     * @throws MathException
     * @throws NumberFormatException
     */
    public function reverseTransform(mixed $value): BigNumber
    {
        if ('' === $value || null === $value) {
            return BigDecimal::zero();
        }

        return $this->scale->toMinorUnit($value, $this->currency);
    }
}
