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

namespace SolidInvoice\MoneyBundle\Tests\Formatter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;

class MoneyFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider localeProvider
     */
    public function testFormatCurrencyWithDefaultValues(string $locale, string $currency, string $format)
    {
        $currency = new Currency($currency);
        $formatter = new MoneyFormatter($locale, $currency);

        $money = new Money(1200, $currency);

        self::assertSame($format, $formatter->format($money));
    }

    /**
     * @dataProvider symbolProvider
     */
    public function testGetCurrencySymbol(string $locale, string $currency, string $symbol)
    {
        $formatter = new MoneyFormatter($locale, new Currency($currency));

        self::assertSame($symbol, $formatter->getCurrencySymbol());
    }

    /**
     * @dataProvider thousandSeparatorProvider
     */
    public function testGetThousandSeparator(string $locale, string $separator)
    {
        $formatter = new MoneyFormatter($locale, new Currency('USD'));

        self::assertSame($separator, $formatter->getThousandSeparator());
    }

    /**
     * @dataProvider decimalSeparatorProvider
     */
    public function testGetDecimalSeparator(string $locale, string $separator)
    {
        $formatter = new MoneyFormatter($locale, new Currency('USD'));

        self::assertSame($separator, $formatter->getDecimalSeparator());
    }

    /**
     * @dataProvider patternProvider
     */
    public function testGetPattern(string $locale, string $pattern)
    {
        $formatter = new MoneyFormatter($locale, new Currency('USD'));

        self::assertStringContainsString($pattern, $formatter->getPattern());
    }

    public function localeProvider(): array
    {
        return [
            [
                'en_US', 'USD', '$12.00',
            ],
            [
                'en_GB', 'GBP', '£12.00',
            ],
            [
                'fr_FR', 'EUR', '12,00 €',
            ],
            [
                'af_ZA', 'ZAR', 'R 12,00',
            ],
        ];
    }

    public function symbolProvider(): array
    {
        return [
            [
                'en_US', 'USD', '$',
            ],
            [
                'en_GB', 'GBP', '£',
            ],
            [
                'fr_FR', 'EUR', '€',
            ],
            [
                'af_ZA', 'ZAR', 'R',
            ],
        ];
    }

    public function thousandSeparatorProvider()
    {
        return [
            [
                'en_US', ',',
            ],
            [
                'en_GB', ',',
            ],
            [
                'fr_FR', ' ',
            ],
            [
                'af_ZA', ' ',
            ],
        ];
    }

    public function decimalSeparatorProvider()
    {
        return [
            [
                'en_US', '.',
            ],
            [
                'en_GB', '.',
            ],
            [
                'fr_FR', ',',
            ],
            [
                'af_ZA', ',',
            ],
        ];
    }

    public function patternProvider()
    {
        return [
            [
                'en_US', '%s%v',
            ],
            [
                'en_GB', '%s%v',
            ],
            [
                'fr_FR', '%v %s',
            ],
            [
                'af_ZA', '%s%v',
            ],
        ];
    }
}
