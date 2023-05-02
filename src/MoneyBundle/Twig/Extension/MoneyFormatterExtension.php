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
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function is_int;

/**
 * @see \SolidInvoice\MoneyBundle\Tests\Twig\Extension\MoneyFormatterExtensionTest
 */
class MoneyFormatterExtension extends AbstractExtension
{
    public function __construct(
        private readonly MoneyFormatterInterface $formatter,
        private readonly SystemConfig $systemConfig
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('currencyFormatter', fn () => $this->formatter),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('formatCurrency', function ($money, ?Currency $currency = null): string {
                if (null === $money) {
                    if ($currency instanceof Currency) {
                        return $this->formatter->format(new Money(0, $currency));
                    }

                    return $this->formatter->format(new Money(0, $this->systemConfig->getCurrency()));
                }

                if (is_int($money)) {
                    return $this->formatter->format(new Money($money, $this->systemConfig->getCurrency()));
                }

                return $this->formatter->format($money);
            }),
        ];
    }
}
