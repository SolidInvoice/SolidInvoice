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
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Money;
use NumberFormatter;
use Override;
use RuntimeException;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use function is_string;
use function sprintf;

/**
 * @see \SolidInvoice\MoneyBundle\Tests\Twig\Extension\MoneyFormatterExtensionTest
 */
class MoneyFormatterExtension extends AbstractExtension
{
    private readonly ISOCurrencies $currencies;

    /**
     * Amount-only formatting needs a locale-aware decimal formatter. It is built once
     * here rather than per filter call: a dashboard renders dozens of amounts, and
     * only the fraction digits vary between them.
     *
     * `ext-intl` is a hard requirement of this project (composer.json), so the
     * polyfill fallback `MoneyFormatter` carries for the CURRENCY style cannot be
     * reached from here and is deliberately not repeated.
     */
    private readonly NumberFormatter $numberFormatter;

    /**
     * $locale is autowired from the `$locale` binding in the bundle's services.php.
     * The default only applies to direct instantiation outside the container.
     */
    public function __construct(
        private readonly MoneyFormatterInterface $formatter,
        private readonly SystemConfig $systemConfig,
        string $locale = 'en'
    ) {
        $this->currencies = new ISOCurrencies();
        $this->numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('currencyFormatter', fn () => $this->formatter),
        ];
    }

    /**
     * @return TwigFilter[]
     */
    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('formatCurrency', function (BigNumber | int | float | string $value, Currency | string | null $currency = null): string {
                $currency = $this->resolveCurrency($currency);

                $value = BigNumber::of(is_float($value) ? (string) $value : $value)->toBigDecimal();

                if ($value->getScale() > 0) {
                    $value = $value->toScale(0, RoundingMode::HalfEven);
                }

                return $this
                    ->formatter
                    ->format(new Money((string) $value, $currency ?? $this->systemConfig->getCurrency()));
            }),

            /*
             * The amount, localised, with no currency symbol and no currency code.
             *
             * This exists for the `.money` component (DESIGN.md section 5), which splits the
             * two apart: `.money-currency` renders the ISO code and `.money-amount`
             * renders the digits. Feeding `formatCurrency` into that contract would
             * print the currency twice - "USD $1,234.56".
             *
             * Fraction digits come from the currency itself, never a hardcoded 2:
             * JPY has 0, and "JPY 1,000.00" would be wrong by two orders of magnitude
             * on sight.
             */
            new TwigFilter('formatCurrencyAmount', function (BigNumber | int | float | string $value, Currency | string | null $currency = null): string {
                $currency = $this->resolveCurrency($currency) ?? $this->systemConfig->getCurrency();

                $value = BigNumber::of(is_float($value) ? (string) $value : $value)->toBigDecimal();

                /*
                 * Mirrors `formatCurrency` deliberately. The incoming value is a count of
                 * minor units, and `Money` accepts only a whole number of them - which is
                 * why the rounding is to scale 0 rather than to 2 decimal places. Half-even
                 * is the same banker's rounding the other filter applies. The two filters
                 * describe the same amount and must never disagree about it, so this
                 * mirrors rather than diverges.
                 */
                if ($value->getScale() > 0) {
                    $value = $value->toScale(0, RoundingMode::HalfEven);
                }

                $fractionDigits = $this->currencies->subunitFor($currency);

                $this->numberFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $fractionDigits);
                $this->numberFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $fractionDigits);

                $formatted = $this->numberFormatter->format($value->withPointMovedLeft($fractionDigits)->toFloat());

                if ($formatted === false) {
                    throw new RuntimeException(sprintf('Unable to format amount "%s" for currency "%s".', $value, $currency->getCode()));
                }

                return $formatted;
            }),
        ];
    }

    /**
     * An empty string means "not supplied" and falls back to the system currency, the same as null.
     */
    private function resolveCurrency(Currency | string | null $currency): ?Currency
    {
        if (is_string($currency) && $currency !== '') {
            $currency = new Currency($currency);
        } elseif (is_string($currency) && $currency === '') {
            $currency = null;
        }

        return $currency;
    }
}
