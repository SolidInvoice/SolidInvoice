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

namespace SolidInvoice\MoneyBundle\Twig\Extension;

use Money\Currency;
use Money\Money;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * @see \SolidInvoice\MoneyBundle\Tests\Twig\Extension\MoneyFormatterExtensionTest
 */
class MoneyFormatterExtension extends AbstractExtension
{
    private MoneyFormatterInterface $formatter;

    private Currency $currency;

    public function __construct(MoneyFormatterInterface $formatter, Currency $currency)
    {
        $this->formatter = $formatter;
        $this->currency = $currency;
    }

    public function getFunctions(): array
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
                if (! $money instanceof Money && is_numeric($money)) {
                    @trigger_error('Passing a number to "formatCurrency" is deprecated since version 2.0.1 and will be unsupported in version 2.1. Pass a Money instance instead.', E_USER_DEPRECATED);
                    $money = new Money((int) $money, $currency ? new Currency($currency) : $this->currency);
                }

                return $this->formatter->format($money);
            }),
        ];
    }
}
