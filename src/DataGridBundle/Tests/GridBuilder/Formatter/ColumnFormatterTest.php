<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\Tests\GridBuilder\Formatter;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\CurrencyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\UrlColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\ColumnFormatter;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\CurrencyFormatter;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\DateTimeFormatter;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\StringFormatter;
use SolidInvoice\DataGridBundle\GridBuilder\Formatter\UrlFormatter;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

/**
 * @covers \SolidInvoice\DataGridBundle\GridBuilder\Formatter\ColumnFormatter
 */
final class ColumnFormatterTest extends TestCase
{
    private ColumnFormatter $formatter;

    /**
     * @var ServiceLocator<string>&MockObject
     */
    private ServiceLocator&MockObject $locator;

    protected function setUp(): void
    {
        $this->locator = $this->createMock(ServiceLocator::class);
        $this->formatter = new ColumnFormatter($this->locator);
    }

    public function testFormatReturnsCorrectValueForSupportedColumn(): void
    {
        $column = CurrencyColumn::new('currency');
        $formatter = new CurrencyFormatter('en_US');

        $this->locator->method('has')->willReturn(true);
        $this->locator->method('get')->willReturn($formatter);

        $this->assertSame('US Dollar', $this->formatter->format($column, 'USD'));
    }

    public function testFormatReturnsCorrectValueForUnsupportedColumn(): void
    {
        $column = $this->createMock(Column::class);
        $formatter = new StringFormatter(new Environment(new ArrayLoader()));

        $this->locator->method('has')->willReturn(false);
        $this->locator->method('get')->willReturn($formatter);

        $this->assertSame('value', $this->formatter->format($column, 'value'));
    }

    public function testGetSubscribedServicesReturnsCorrectServices(): void
    {
        $services = ColumnFormatter::getSubscribedServices();

        $this->assertSame([
            CurrencyColumn::class => CurrencyFormatter::class,
            DateTimeColumn::class => DateTimeFormatter::class,
            StringColumn::class => StringFormatter::class,
            UrlColumn::class => UrlFormatter::class,
        ], $services);
    }
}
