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

namespace SolidInvoice\MoneyBundle\Currency;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Money\Currencies;
use Money\Currencies\ISOCurrencies;
use Money\Currency;

/**
 * Converts between stored minor units and entered/displayed major units. The factor follows the
 * currency's own decimal count, not a fixed 100 - assuming 100 everywhere is what put JPY and
 * BHD amounts out by two and one orders of magnitude respectively.
 *
 * @see \SolidInvoice\MoneyBundle\Tests\Currency\CurrencyScaleTest
 */
final readonly class CurrencyScale
{
    /**
     * Used when a currency is not part of the ISO set, so that an unrecognised code degrades
     * to the historical behaviour instead of failing the whole request.
     */
    private const int DEFAULT_SUBUNIT = 2;

    public function __construct(
        private Currencies $currencies = new ISOCurrencies(),
    ) {
    }

    public function subunitFor(Currency $currency): int
    {
        if (! $this->currencies->contains($currency)) {
            return self::DEFAULT_SUBUNIT;
        }

        return $this->currencies->subunitFor($currency);
    }

    public function factorFor(Currency $currency): int
    {
        return 10 ** $this->subunitFor($currency);
    }

    /**
     * Scales without rounding, so a value carrying more precision than the currency allows keeps
     * its fraction (0.5 JPY stays 0.5). Callers that need a whole number of minor units - anything
     * building a Money directly rather than persisting through the integer column - must round.
     *
     * @throws MathException
     */
    public function toMinorUnit(BigNumber | float | int | string $value, Currency $currency): BigDecimal
    {
        return $this->toBigDecimal($value)->multipliedBy($this->factorFor($currency));
    }

    /**
     * Returns a float rather than a BigDecimal because both callers feed a display layer that
     * needs a primitive (a form view and a chart dataset). Do not reuse this where the exact
     * value matters - go through toMinorUnit and keep the BigDecimal instead.
     *
     * @throws MathException
     */
    public function toMajorUnit(BigNumber | float | int | string $value, Currency $currency): float
    {
        $subunit = $this->subunitFor($currency);

        return $this->toBigDecimal($value)
            ->dividedBy(10 ** $subunit, $subunit, RoundingMode::HalfEven)
            ->toFloat();
    }

    /**
     * @throws MathException
     */
    private function toBigDecimal(BigNumber | float | int | string $value): BigDecimal
    {
        return BigNumber::of(is_float($value) ? (string) $value : $value)->toBigDecimal();
    }
}
