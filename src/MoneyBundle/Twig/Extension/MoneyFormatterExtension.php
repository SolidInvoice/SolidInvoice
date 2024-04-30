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

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Money\Currency;
use Money\Money;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function is_string;

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
            new TwigFilter('formatCurrency', function (BigNumber|int|float|string $value, Currency|string|null $currency = null): string {
                if (is_string($currency) && $currency !== '') {
                    $currency = new Currency($currency);
                }

                $value = BigNumber::of($value)->toBigDecimal();

                if ($value->getScale() > 0) {
                    $value = $value->toScale(0, RoundingMode::HALF_EVEN);
                }

                return $this
                    ->formatter
                    ->format(new Money((string) $value, $currency ?? $this->systemConfig->getCurrency()));
            }),
        ];
    }
}
