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
use Mockery as M;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\SettingsBundle\SystemConfig;

class MoneyFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @dataProvider localeProvider
     */
    public function testFormatCurrencyWithDefaultValues(string $locale, string $currency, string $format): void
    {
        $systemConfig = $this->getSystemConfigMock($currency);

        $formatter = new MoneyFormatter($locale, $systemConfig);

        $money = new Money(1200, new Currency($currency));

        self::assertSame($format, $formatter->format($money));
    }

    /**
     * @dataProvider symbolProvider
     */
    public function testGetCurrencySymbol(string $locale, string $currency, string $symbol): void
    {
        $systemConfig = $this->getSystemConfigMock($currency);

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertSame($symbol, $formatter->getCurrencySymbol());
        self::assertSame($symbol, $formatter->getCurrencySymbol($currency));
    }

    /**
     * @dataProvider thousandSeparatorProvider
     */
    public function testGetThousandSeparator(string $locale, string $separator): void
    {
        $systemConfig = $this->getSystemConfigMock();

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertSame($separator, $formatter->getThousandSeparator());
    }

    /**
     * @dataProvider decimalSeparatorProvider
     */
    public function testGetDecimalSeparator(string $locale, string $separator): void
    {
        $systemConfig = $this->getSystemConfigMock();

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertSame($separator, $formatter->getDecimalSeparator());
    }

    /**
     * @dataProvider patternProvider
     */
    public function testGetPattern(string $locale, string $pattern): void
    {
        $systemConfig = $this->getSystemConfigMock();

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertStringContainsString($pattern, $formatter->getPattern());
    }

    /**
     * @return array<array<string>>
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
                'af_ZA', 'ZAR', 'R 12,00',
            ],
        ];
    }

    /**
     * @return array<array<string>>
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

    /**
     * @return array<array<string>>
     */
    public function thousandSeparatorProvider(): array
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

    /**
     * @return array<array<string>>
     */
    public function decimalSeparatorProvider(): array
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

    /**
     * @return array<array<string>>
     */
    public function patternProvider(): array
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

    /**
     * @return M\MockInterface&SystemConfig
     */
    private function getSystemConfigMock(string $currency = 'USD'): M\MockInterface
    {
        $systemConfig = M::mock(SystemConfig::class);

        $systemConfig
            ->shouldReceive('getCurrency')
            ->zeroOrMoreTimes()
            ->andReturn(new Currency($currency));

        return $systemConfig;
    }
}
