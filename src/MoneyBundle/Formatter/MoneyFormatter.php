<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MoneyBundle\Formatter;

use Money\Currency;
use Money\Money;
use Symfony\Component\Intl\Exception\MethodArgumentNotImplementedException;
use Symfony\Component\Intl\Exception\MethodArgumentValueNotImplementedException;
use Symfony\Component\Intl\Intl;
use Money\MoneyFormatter as MoneyFormatterInterface;

class MoneyFormatter implements MoneyFormatterInterface
{
    /**
     * @var string
     */
    private $locale;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @var \NumberFormatter
     */
    private $numberFormatter;

    /**
     * @param string          $locale
     * @param Currency|string $currency
     *
     * @throws MethodArgumentNotImplementedException
     * @throws MethodArgumentValueNotImplementedException
     */
    public function __construct(string $locale, Currency $currency)
    {
        try {
            $this->numberFormatter = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        } catch (MethodArgumentValueNotImplementedException | MethodArgumentNotImplementedException $e) {
            $this->numberFormatter = new \NumberFormatter('en', \NumberFormatter::CURRENCY);
        }

        $this->numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, 2);
        $this->locale = $locale;
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
