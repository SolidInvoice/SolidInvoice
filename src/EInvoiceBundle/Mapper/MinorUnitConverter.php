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

namespace SolidInvoice\EInvoiceBundle\Mapper;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Money\Currency;
use SolidInvoice\MoneyBundle\Currency\CurrencyScale;

/**
 * The only place a stored minor-unit amount becomes the major-unit `BigDecimal` the `Model/`
 * layer requires (design §4.4, ADR §4.2). `CurrencyScale::toMajorUnit()` must not be used here:
 * it returns a `float`.
 * @see \SolidInvoice\EInvoiceBundle\Tests\Mapper\MinorUnitConverterTest
 */
final readonly class MinorUnitConverter
{
    public function __construct(
        private CurrencyScale $currencyScale,
    ) {
    }

    /**
     * Exact. Dividing by a power of ten is always representable, so `RoundingMode::Unnecessary`
     * can never throw. A value carrying more precision than the currency allows keeps it, rather
     * than being truncated, so a validator can still report it.
     */
    public function toMajorUnit(BigNumber $minorUnits, Currency $currency): BigDecimal
    {
        $value = $minorUnits->toBigDecimal();
        $subunit = $this->currencyScale->subunitFor($currency);

        return $value->dividedBy(10 ** $subunit, $value->getScale() + $subunit, RoundingMode::Unnecessary);
    }
}
