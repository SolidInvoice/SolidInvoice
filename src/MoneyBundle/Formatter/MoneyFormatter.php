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

use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\Intl\Exception\MethodArgumentNotImplementedException;
use Symfony\Component\Intl\Exception\MethodArgumentValueNotImplementedException;

/**
 * @see \SolidInvoice\MoneyBundle\Tests\Formatter\MoneyFormatterTest
 */
final class MoneyFormatter implements MoneyFormatterInterface
{
    private readonly string $locale;

    private readonly \Money\MoneyFormatter $formatter;

    private NumberFormatter $numberFormatter;

    /**
     * @throws MethodArgumentNotImplementedException|MethodArgumentValueNotImplementedException
     */
    public function __construct(
        string $locale,
        private readonly SystemConfig $systemConfig
    ) {
        try {
            $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        } catch (MethodArgumentValueNotImplementedException|MethodArgumentNotImplementedException) {
            $this->numberFormatter = new NumberFormatter('en', NumberFormatter::CURRENCY);
        }

        $this->numberFormatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);
        $this->formatter = new IntlMoneyFormatter($this->numberFormatter, new ISOCurrencies());
        $this->locale = $locale;
    }

    public function format(Money $money): string
    {
        return $this->formatter->format($money);
    }

    public function getCurrencySymbol($currency = null): string
    {
        $currency = $this->getCurrency($currency);

        if (null === $currency) {
            return '';
        }

        return Currencies::getSymbol($currency, $this->locale);
    }

    public function getThousandSeparator(): string
    {
        return $this->numberFormatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    public function getDecimalSeparator(): string
    {
        return $this->numberFormatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
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

    private function getCurrency(Currency|string|null $currency): ?string
    {
        if ($currency instanceof Currency) {
            $currency = $currency->getCode();
        }

        if (null === $currency) {
            $currency = $this->systemConfig->getCurrency();

            if (! $currency instanceof Currency) {
                return null;
            }

            $currency = $currency->getCode();
        }

        return $currency;
    }
}
