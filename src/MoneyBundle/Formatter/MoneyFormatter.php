<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Formatter;

use Money\Currency;
use Money\Money;
use Symfony\Component\Intl\Intl;

class MoneyFormatter
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var \Money\Currency
     */
    private $currency;

    /**
     * @var \NumberFormatter
     */
    private $numberFormatter;

    /**
     * @param string          $locale
     * @param Currency|string $currency
     */
    public function __construct(string $locale, $currency = null)
    {
        $this->numberFormatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $this->numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
        $this->locale = $locale;

        if (!empty($currency) && !$currency instanceof Currency) {
            $currency = new Currency($currency);
        }

        $this->currency = $currency;
    }

    /**
     * @param \Money\Money $money
     *
     * @return string
     */
    public function format(Money $money): string
    {
        $amount = $this->toFloat($money);

        return $this->numberFormatter->formatCurrency($amount, $money->getCurrency()->getCode());
    }

    /**
     * @param Currency|string $currency
     *
     * @return string
     */
    public function getCurrencySymbol($currency = null): string
    {
        if ($currency instanceof Currency) {
            $currency = $currency->getCode();
        }

        return Intl::getCurrencyBundle()
            ->getCurrencySymbol($currency ?: $this->currency->getCode(), $this->locale);
    }

    /**
     * return string.
     */
    public function getThousandSeparator()
    {
        return $this->numberFormatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    /**
     * @return string
     */
    public function getDecimalSeparator(): string
    {
        return $this->numberFormatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        if (extension_loaded('intl')) {
            $pattern = explode(';', $this->numberFormatter->getPattern());

            return str_replace(['¤', '#,##0.00'], ['%s', '%v'], $pattern[0]);
        }

        return '%s%v';
    }

    /**
     * @param Money $amount
     *
     * @return float
     */
    public static function toFloat(Money $amount): float
    {
        return ((float) $amount->getAmount()) / pow(10, 2);
    }
}
