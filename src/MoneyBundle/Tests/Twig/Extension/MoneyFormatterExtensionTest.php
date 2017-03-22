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

namespace CSBill\MoneyBundle\Tests\Twig\Extension;

use CSBill\MoneyBundle\Formatter\MoneyFormatter;
use CSBill\MoneyBundle\Twig\Extension\MoneyFormatterExtension;
use Mockery as M;
use Money\Currency;
use Money\Money;

class MoneyFormatterExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testGetFunctions()
    {
        $moneyFormatter = new MoneyFormatter('en_US');
        $extension = new MoneyFormatterExtension($moneyFormatter, new Currency('USD'));

        $this->assertSame('currency_formatter', $extension->getName());

        /** @var \Twig_SimpleFunction[] $functions */
        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);

        $this->assertInstanceOf('Twig_SimpleFunction', $functions[0]);
        $this->assertSame('currencyFormatter', $functions[0]->getName());
        $this->assertSame($moneyFormatter, call_user_func($functions[0]->getCallable()));
    }

    public function testGetFilters()
    {
        $money = new Money(1200, new Currency('USD'));

        $moneyFormatter = M::mock('CSBill\MoneyBundle\Formatter\MoneyFormatter', ['en_USD']);
        $moneyFormatter
            ->shouldReceive('format')
            ->once()
            ->with($money)
            ->andReturn('$12,00');

        $extension = new MoneyFormatterExtension($moneyFormatter, new Currency('USD'));

        /** @var \Twig_SimpleFilter[] $filters */
        $filters = $extension->getFilters();

        $this->assertCount(1, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[0]);
        $this->assertSame('formatCurrency', $filters[0]->getName());
        $this->assertSame('$12,00', call_user_func_array($filters[0]->getCallable(), [$money]));
    }
}
