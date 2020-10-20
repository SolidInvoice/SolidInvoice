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
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MethodArgumentNotImplementedException;
use Symfony\Component\Intl\Exception\MethodArgumentValueNotImplementedException;

final class MoneyFormatter implements MoneyFormatterInterface
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
     * @param Currency|string $currency
     *
     * @throws MethodArgumentNotImplementedException | MethodArgumentValueNotImplementedException
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

    public function format(Money $money): string
    {
        $amount = self::toFloat($money);

        return $this->numberFormatter->formatCurrency($amount, $money->getCurrency()->getCode());
    }

    public function getCurrencySymbol($currency = null): string
    {
        if ($currency instanceof Currency) {
            $currency = $currency->getCode();
        }

        return Currencies::getSymbol($currency ?: $this->currency->getCode(), $this->locale);
    }

    public function getThousandSeparator(): string
    {
        return $this->numberFormatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    public function getDecimalSeparator(): string
    {
        return $this->numberFormatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    public function getPattern(): string
    {
        if (extension_loaded('intl')) {
            $pattern = explode(';', $this->numberFormatter->getPattern());

            return str_replace(['¤', '#,##0.00'], ['%s', '%v'], $pattern[0]);
        }

        return '%s%v';
    }

    public static function toFloat(Money $amount): float
    {
        return ((float) $amount->getAmount()) / (10 ** 2);
    }
}
