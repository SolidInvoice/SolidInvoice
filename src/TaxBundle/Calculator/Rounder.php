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

namespace SolidInvoice\TaxBundle\Calculator;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use SolidInvoice\TaxBundle\Enum\RoundingStrategy;

/**
 * Rounds monetary amounts to a given scale using a configurable rounding strategy.
 *
 * Defaults to {@see RoundingStrategy::HalfEven} ("banker's rounding"), which matches
 * the behaviour of the legacy {@see \SolidInvoice\CoreBundle\Billing\TotalCalculator}.
 * @see \SolidInvoice\TaxBundle\Tests\Calculator\RounderTest
 */
final readonly class Rounder
{
    public function __construct(
        private RoundingStrategy $strategy = RoundingStrategy::HalfEven,
    ) {
    }

    public function getStrategy(): RoundingStrategy
    {
        return $this->strategy;
    }

    /**
     * @throws MathException
     */
    public function round(BigNumber|int|string|float $value, int $scale = 0): BigDecimal
    {
        $normalised = is_float($value) ? (string) $value : $value;

        return BigNumber::of($normalised)->toBigDecimal()->toScale($scale, $this->strategy->toRoundingMode());
    }

    public function withStrategy(RoundingStrategy $strategy): self
    {
        return new self($strategy);
    }
}
