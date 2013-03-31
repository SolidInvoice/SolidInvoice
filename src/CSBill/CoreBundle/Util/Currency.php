<?php

namespace CSBill\CoreBundle\Util;

use NumberFormatter;

class Currency
{
    protected $formatter;

    protected $locale;
    protected $currency;

    public function __construct($locale, $currency)
    {
        $this->locale = $locale;
        $this->currency = $currency;

        $this->formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    }

    public function getThousandSeparator()
    {
        return $this->formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    public function getDecimalSeparator()
    {
        return $this->formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    public function getCurrencySymbol($currency = null)
    {
        $pattern = $this->format(123, $currency);

        preg_match('/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u', $pattern, $matches);

        if (!empty($matches[1])) {
            return $matches[1];
        } elseif (!empty($matches[2])) {
            return $matches[2];
        }

        return null;
    }

    public function format($value, $currency = null)
    {
        return $this->formatter->formatCurrency($value, $currency ?: $this->currency);
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getLocale()
    {
        return $this->locale;
    }
}
