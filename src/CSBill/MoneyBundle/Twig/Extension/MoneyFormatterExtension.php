<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Twig\Extension;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Money\Currency;
use Money\Money;

class MoneyFormatterExtension extends \Twig_Extension
{
    /**
     * @var MoneyFormatter
     */
    private $formatter;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * @param MoneyFormatter $formatter
     * @param Currency       $currency
     */
    public function __construct(MoneyFormatter $formatter, Currency $currency)
    {
        $this->formatter = $formatter;
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('currencyFormatter', function () {
                return $this->formatter;
            }),
        ];
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('formatCurrency', function ($money) {
                if (!$money instanceof Money && is_int($money)) {
                    $money = new Money($money, $this->currency);
                }

                return $this->formatter->format($money);
            }),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'currency_formatter';
    }
}
