<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\MoneyBundle\Tests\Formatter;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MoneyFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @param string $locale
     * @param string $currency
     * @param string $format
     *
     * @dataProvider localeProvider
     */
    public function testFormatCurrencyWithDefaultValues(string $locale, string $currency, string $format)
    {
        $currency = new Currency($currency);
        $formatter = new MoneyFormatter($locale, $currency);

        $money = new Money(1200, $currency);

        $this->assertSame($format, $formatter->format($money));
    }

    /**
     * @param string $locale
     * @param string $currency
     * @param string $symbol
     *
     * @dataProvider symbolProvider
     */
    public function testGetCurrencySymbol(string $locale, string $currency, string $symbol)
    {
        $formatter = new MoneyFormatter($locale, new Currency($currency));

        $this->assertSame($symbol, $formatter->getCurrencySymbol());
    }

    /**
     * @param string $locale
     * @param string $separator
     *
     * @dataProvider thousandSeparatorProvider
     */
    public function testGetThousandSeparator(string $locale, string $separator)
    {
        $formatter = new MoneyFormatter($locale, new Currency('USD'));

        $this->assertEquals($separator, $formatter->getThousandSeparator());
    }

    /**
     * @param string $locale
     * @param string $separator
     *
     * @dataProvider decimalSeparatorProvider
     */
    public function testGetDecimalSeparator(string $locale, string $separator)
    {
        $formatter = new MoneyFormatter($locale, new Currency('USD'));

        $this->assertEquals($separator, $formatter->getDecimalSeparator());
    }

    /**
     * @param string $locale
     * @param string $pattern
     *
     * @dataProvider patternProvider
     */
    public function testGetPattern(string $locale, string $pattern)
    {
        $formatter = new MoneyFormatter($locale, new Currency('USD'));

        $this->assertContains($pattern, $formatter->getPattern());
    }

    /**
     * @return array
     */
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
                'af_ZA', 'ZAR', 'R12,00',
            ],
        ];
    }

    /**
     * @return array
     */
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
                'fr_FR', ' ',
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
