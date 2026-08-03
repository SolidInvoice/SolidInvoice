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
 * Converts between the minor units amounts are stored in and the major units they are entered
 * and displayed in, using the number of decimal places the currency actually has.
 *
 * Most currencies have two decimals (USD 1.00 is stored as 100), but JPY has none (¥800 is
 * stored as 800) and BHD has three (BD 1.234 is stored as 1234). Assuming two decimals
 * everywhere is what made zero- and three-decimal currencies render at the wrong magnitude.
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
