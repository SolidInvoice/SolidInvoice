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

use Money\Currency;
use Money\Money;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MoneyFormatterExtension extends AbstractExtension
{
    /**
     * @var MoneyFormatter
     */
    private $formatter;

    /**
     * @var Currency
     */
    private $currency;

    public function __construct(MoneyFormatterInterface $formatter, Currency $currency)
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
            new TwigFunction('currencyFormatter', function () {
                return $this->formatter;
            }),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('formatCurrency', function ($money, $currency = null): string {
                if (!$money instanceof Money && is_numeric($money)) {
                    @trigger_error('Passing a number to "formatCurrency" is deprecated since version 2.0.1 and will be unsupported in version 2.1. Pass a Money instance instead.', E_USER_DEPRECATED);
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
