<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Util;

use NumberFormatter;

class Currency
{
    /**
     * @var \NumberFormatter
     */
    protected $formatter;

    /**
     * @var string
     */
    protected $locale;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @param string $locale
     * @param string $currency
     */
    public function __construct($locale, $currency)
    {
        $this->locale = $locale;
        $this->currency = $currency;
        $this->formatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
    }

    /**
     * @return string
     */
    public function getThousandSeparator()
    {
        return $this->formatter->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
    }

    /**
     * @return string
     */
    public function getDecimalSeparator()
    {
        return $this->formatter->getSymbol(NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);
    }

    /**
     * @param string|null $currency
     *
     * @return string|null
     */
    public function getCurrencySymbol($currency = null)
    {
        $pattern = $this->format(123, $currency);

        preg_match('/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u', $pattern, $matches);

        if (!empty($matches[1])) {
            return $matches[1];
        } elseif (!empty($matches[2])) {
            return $matches[2];
        }

        return;
    }

    /**
     * @param int    $value
     * @param string $currency
     *
     * @return string
     */
    public function format($value, $currency = null)
    {
        return $this->formatter->formatCurrency($value, $currency ?: $this->currency);
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
}
