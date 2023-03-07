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
use SolidInvoice\MoneyBundle\Entity\Money;
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

    public function __construct(MoneyFormatterInterface $formatter)
    {
        $this->formatter = $formatter;
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
            new TwigFilter('formatCurrency', function (?Money $money, ?Currency $currency = null): string {
                return $this->formatter->format($money, $currency);
            }),
        ];
    }
}
