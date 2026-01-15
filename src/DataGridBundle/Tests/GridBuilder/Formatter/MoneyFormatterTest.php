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

use Money\Currency;
use Money\Money;
use PHPUnit\Framework\MockObject\MockObject;
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
    private MoneyFormatter $formatter;

    private SystemConfig&MockObject $config;

    private MoneyFormatterInterface&MockObject $moneyFormatter;

    protected function setUp(): void
    {
        $this->config = $this->createMock(SystemConfig::class);
        $this->moneyFormatter = $this->createMock(MoneyFormatterInterface::class);

        $this->formatter = new MoneyFormatter($this->config, $this->moneyFormatter);
    }

    public function testFormatWithMoneyObject(): void
    {
        $money = new Money(10000, new Currency('USD'));

        $this->moneyFormatter
            ->expects($this->once())
            ->method('format')
            ->with($money)
            ->willReturn('$100.00');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, $money);

        self::assertSame('$100.00', $result);
    }

    public function testFormatWithNumericValueCreatesMoneyObject(): void
    {
        $currency = new Currency('EUR');

        $this->config
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->moneyFormatter
            ->expects($this->once())
            ->method('format')
            ->with($this->callback(function (Money $money) use ($currency) {
                return $money->getAmount() === '5000'
                    && $money->getCurrency()->equals($currency);
            }))
            ->willReturn('€50.00');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, 5000);

        self::assertSame('€50.00', $result);
    }

    public function testFormatWithStringValueCreatesMoneyObject(): void
    {
        $currency = new Currency('GBP');

        $this->config
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->moneyFormatter
            ->expects($this->once())
            ->method('format')
            ->willReturn('£25.50');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, '2550');

        self::assertSame('£25.50', $result);
    }

    public function testFormatUsesSystemCurrencyForNonMoneyValues(): void
    {
        $currency = new Currency('JPY');

        $this->config
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->moneyFormatter
            ->expects($this->once())
            ->method('format')
            ->with($this->callback(function (Money $money) {
                return $money->getCurrency()->getCode() === 'JPY';
            }))
            ->willReturn('¥1,000');

        $column = MoneyColumn::new('amount');
        $result = $this->formatter->format($column, 1000);

        self::assertSame('¥1,000', $result);
    }
}
