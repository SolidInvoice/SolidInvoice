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

namespace SolidInvoice\MoneyBundle\Tests\Twig\Extension;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatterInterface;
use SolidInvoice\MoneyBundle\Twig\Extension\MoneyFormatterExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class MoneyFormatterExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetFunctions(): void
    {
        $currency = new Currency('USD');
        $moneyFormatter = new MoneyFormatter('en_US', $currency);
        $extension = new MoneyFormatterExtension($moneyFormatter, $currency);

        self::assertSame('currency_formatter', $extension->getName());

        /** @var TwigFunction[] $functions */
        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);

        self::assertInstanceOf(Twig_SimpleFunction::class, $functions[0]);
        self::assertSame('currencyFormatter', $functions[0]->getName());
        self::assertSame($moneyFormatter, call_user_func($functions[0]->getCallable()));
    }

    public function testGetFilters(): void
    {
        $currency = new Currency('USD');
        $money = new Money(1200, $currency);

        $moneyFormatter = M::mock(MoneyFormatterInterface::class);
        $moneyFormatter
            ->shouldReceive('format')
            ->once()
            ->with($money)
            ->andReturn('$12,00');

        $extension = new MoneyFormatterExtension($moneyFormatter, $currency);

        /** @var TwigFilter[] $filters */
        $filters = $extension->getFilters();

        self::assertCount(1, $filters);

        self::assertInstanceOf(Twig_SimpleFilter::class, $filters[0]);
        self::assertSame('formatCurrency', $filters[0]->getName());
        self::assertSame('$12,00', call_user_func_array($filters[0]->getCallable(), [$money]));
    }
}
