<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
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
     * @param string          $locale
     * @param Currency|string $currency
     */
    public function __construct($locale, $currency = null)
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
    public function format(Money $money)
    {
        $amount = $this->toFloat($money);

        return $this->numberFormatter->formatCurrency($amount, $money->getCurrency()->getName());
    }

    /**
     * @return string
     */
    public function getCurrencySymbol()
    {
        return Intl::getCurrencyBundle()
            ->getCurrencySymbol($this->currency->getName(), $this->locale);
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
    public function getDecimalSeparator()
    {
        return $this->numberFormatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    /**
     * @return string
     */
    public function getPattern()
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
    public static function toFloat(Money $amount)
    {
        return ((float) $amount->getAmount()) / pow(10, 2);
    }
}
