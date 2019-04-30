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

namespace SolidInvoice\MoneyBundle\Twig\Extension;

use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use Money\Currency;
use Money\Money;

class MoneyFormatterExtension extends \Twig\Extension\AbstractExtension
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
            new \Twig\TwigFunction('currencyFormatter', function () {
                return $this->formatter;
            }),
        ];
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter('formatCurrency', function ($money, $currency = null): string {
                if (!$money instanceof Money && is_numeric($money)) {
                    $money = new Money((int) $money, $currency ? new Currency($currency) : $this->currency);
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
