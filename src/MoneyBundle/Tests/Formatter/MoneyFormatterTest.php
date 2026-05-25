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

use Iterator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\SettingsBundle\SystemConfig;

final class MoneyFormatterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[DataProvider('localeProvider')]
    public function testFormatCurrencyWithDefaultValues(string $locale, string $currency, string $format): void
    {
        $systemConfig = $this->getSystemConfigMock($currency);

        $formatter = new MoneyFormatter($locale, $systemConfig);

        $money = new Money(1200, new Currency($currency));

        self::assertSame($format, $formatter->format($money));
    }

    #[DataProvider('symbolProvider')]
    public function testGetCurrencySymbol(string $locale, string $currency, string $symbol): void
    {
        $systemConfig = $this->getSystemConfigMock($currency);

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertSame($symbol, $formatter->getCurrencySymbol());
        self::assertSame($symbol, $formatter->getCurrencySymbol($currency));
    }

    #[DataProvider('thousandSeparatorProvider')]
    public function testGetThousandSeparator(string $locale, string $separator): void
    {
        $systemConfig = $this->getSystemConfigMock();

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertSame($separator, $formatter->getThousandSeparator());
    }

    #[DataProvider('decimalSeparatorProvider')]
    public function testGetDecimalSeparator(string $locale, string $separator): void
    {
        $systemConfig = $this->getSystemConfigMock();

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertSame($separator, $formatter->getDecimalSeparator());
    }

    #[DataProvider('patternProvider')]
    public function testGetPattern(string $locale, string $pattern): void
    {
        $systemConfig = $this->getSystemConfigMock();

        $formatter = new MoneyFormatter($locale, $systemConfig);

        self::assertStringContainsString($pattern, $formatter->getPattern());
    }

    /**
     * @return Iterator<(int | string), array<string>>
     */
    public static function localeProvider(): Iterator
    {
        yield [
            'en_US', 'USD', '$12.00',
        ];
        yield [
            'en_GB', 'GBP', '£12.00',
        ];
        yield [
            'fr_FR', 'EUR', '12,00 €',
        ];
        yield [
            'af_ZA', 'ZAR', 'R 12,00',
        ];
    }

    /**
     * @return Iterator<(int | string), array<string>>
     */
    public static function symbolProvider(): Iterator
    {
        yield [
            'en_US', 'USD', '$',
        ];
        yield [
            'en_GB', 'GBP', '£',
        ];
        yield [
            'fr_FR', 'EUR', '€',
        ];
        yield [
            'af_ZA', 'ZAR', 'R',
        ];
    }

    /**
     * @return Iterator<(int | string), array<string>>
     */
    public static function thousandSeparatorProvider(): Iterator
    {
        yield [
            'en_US', ',',
        ];
        yield [
            'en_GB', ',',
        ];
        yield [
            'fr_FR', ' ',
        ];
        yield [
            'af_ZA', ' ',
        ];
    }

    /**
     * @return Iterator<(int | string), array<string>>
     */
    public static function decimalSeparatorProvider(): Iterator
    {
        yield [
            'en_US', '.',
        ];
        yield [
            'en_GB', '.',
        ];
        yield [
            'fr_FR', ',',
        ];
        yield [
            'af_ZA', ',',
        ];
    }

    /**
     * @return Iterator<(int | string), array<string>>
     */
    public static function patternProvider(): Iterator
    {
        yield [
            'en_US', '%s%v',
        ];
        yield [
            'en_GB', '%s%v',
        ];
        yield [
            'fr_FR', '%v %s',
        ];
        yield [
            'af_ZA', '%s%v',
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
