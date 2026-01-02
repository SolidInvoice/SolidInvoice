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

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Formatter;

use Mockery as M;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\MoneyFormatter;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\SettingsBundle\SystemConfig;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Formatter\MoneyFormatter
 */
final class MoneyFormatterTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private MoneyFormatter $formatter;

    private SystemConfig&M\MockInterface $config;

    private MoneyFormatterInterface&M\MockInterface $moneyFormatter;

    protected function setUp(): void
    {
        $this->config = M::mock(SystemConfig::class);
        $this->moneyFormatter = M::mock(MoneyFormatterInterface::class);

        $this->formatter = new MoneyFormatter($this->config, $this->moneyFormatter);
    }

    public function testFormatWithMoneyObject(): void
    {
        $money = new Money(10000, new Currency('USD'));

        $this->moneyFormatter
            ->expects('format')
            ->once()
            ->with($money)
            ->andReturn('$100.00');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, $money);

        self::assertSame('$100.00', $result);
    }

    public function testFormatWithNumericValueCreatesMoneyObject(): void
    {
        $currency = new Currency('EUR');

        $this->config
            ->expects('getCurrency')
            ->once()
            ->andReturn($currency);

        $this->moneyFormatter
            ->expects('format')
            ->once()
            ->with(M::on(function (Money $money) use ($currency) {
                return $money->getAmount() === '5000'
                    && $money->getCurrency()->equals($currency);
            }))
            ->andReturn('€50.00');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, 5000);

        self::assertSame('€50.00', $result);
    }

    public function testFormatWithStringValueCreatesMoneyObject(): void
    {
        $currency = new Currency('GBP');

        $this->config
            ->expects('getCurrency')
            ->once()
            ->andReturn($currency);

        $this->moneyFormatter
            ->expects('format')
            ->once()
            ->andReturn('£25.50');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, '2550');

        self::assertSame('£25.50', $result);
    }

    public function testFormatUsesSystemCurrencyForNonMoneyValues(): void
    {
        $currency = new Currency('JPY');

        $this->config
            ->expects('getCurrency')
            ->once()
            ->andReturn($currency);

        $this->moneyFormatter
            ->expects('format')
            ->once()
            ->with(M::on(function (Money $money) {
                return $money->getCurrency()->getCode() === 'JPY';
            }))
            ->andReturn('¥1,000');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, 1000);

        self::assertSame('¥1,000', $result);
    }
}
