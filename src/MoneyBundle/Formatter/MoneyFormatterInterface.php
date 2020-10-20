<?php

declare(strict_types=1);

/*
 * This file is part of the SolidInvoice project.
 *
 * @author     pierre
 * @copyright  Copyright (c) 2020
 */

namespace SolidInvoice\MoneyBundle\Formatter;

use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;

interface MoneyFormatterInterface extends MoneyFormatter
{
    /**
     * @param Currency|string $currency
     *
     * @return string
     */
    public function getCurrencySymbol($currency = null): string;

    public function getThousandSeparator(): string;

    public function getDecimalSeparator(): string;

    public function getPattern(): string;

    public static function toFloat(Money $amount): float;
}