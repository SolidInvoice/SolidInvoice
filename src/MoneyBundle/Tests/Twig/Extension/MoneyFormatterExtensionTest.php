<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\MoneyBundle\Tests\Twig\Extension;

use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\MoneyBundle\Twig\Extension\MoneyFormatterExtension;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;

class MoneyFormatterExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetFunctions()
    {
        $currency = new Currency('USD');
        $moneyFormatter = new MoneyFormatter('en_US', $currency);
        $extension = new MoneyFormatterExtension($moneyFormatter, $currency);

        $this->assertSame('currency_formatter', $extension->getName());

        /** @var \Twig\TwigFunction[] $functions */
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);

        $this->assertInstanceOf('Twig_SimpleFunction', $functions[0]);
        $this->assertSame('currencyFormatter', $functions[0]->getName());
        $this->assertSame($moneyFormatter, call_user_func($functions[0]->getCallable()));
    }

    public function testGetFilters()
    {
        $currency = new Currency('USD');
        $money = new Money(1200, $currency);

        $moneyFormatter = M::mock('SolidInvoice\MoneyBundle\Formatter\MoneyFormatter', ['en_USD', $currency]);
        $moneyFormatter
            ->shouldReceive('format')
            ->once()
            ->with($money)
            ->andReturn('$12,00');

        $extension = new MoneyFormatterExtension($moneyFormatter, $currency);

        /** @var \Twig\TwigFilter[] $filters */
        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertSame('formatCurrency', $filters[0]->getName());
        $this->assertSame('$12,00', call_user_func_array($filters[0]->getCallable(), [$money]));
    }
}
