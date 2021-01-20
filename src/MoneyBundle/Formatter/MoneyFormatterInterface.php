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

namespace SolidInvoice\MoneyBundle\Formatter;

use Money\Currency;
use Money\Money;
use Money\MoneyFormatter;

interface MoneyFormatterInterface extends MoneyFormatter
{
    /**
     * @param Currency|string $currency
     */
    public function getCurrencySymbol($currency = null): string;

    public function getThousandSeparator(): string;

    public function getDecimalSeparator(): string;

    public function getPattern(): string;

    public static function toFloat(Money $amount): float;
}
