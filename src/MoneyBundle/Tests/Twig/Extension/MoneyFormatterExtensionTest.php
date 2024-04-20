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
use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Formatter\MoneyFormatter;
use SolidInvoice\MoneyBundle\Twig\Extension\MoneyFormatterExtension;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twig\TwigFilter;
use Twig\TwigFunction;

class MoneyFormatterExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetFunctions(): void
    {
        $systemConfig = $this->createMock(SystemConfig::class);

        $moneyFormatter = new MoneyFormatter('en_US', $systemConfig);
        $extension = new MoneyFormatterExtension($moneyFormatter, $systemConfig);

        $functions = $extension->getFunctions();

        self::assertCount(1, $functions);

        self::assertInstanceOf(TwigFunction::class, $functions[0]);
        self::assertSame('currencyFormatter', $functions[0]->getName());
        self::assertSame($moneyFormatter, call_user_func($functions[0]->getCallable()));
    }

    public function testGetFilters(): void
    {
        $systemConfig = $this->createMock(SystemConfig::class);

        $systemConfig
            ->expects(self::once())
            ->method('getCurrency')
            ->willReturn(new Currency('USD'));

        $moneyFormatter = new MoneyFormatter('en_US', $systemConfig);
        $extension = new MoneyFormatterExtension($moneyFormatter, $systemConfig);

        $filters = $extension->getFilters();

        self::assertCount(1, $filters);

        self::assertInstanceOf(TwigFilter::class, $filters[0]);
        self::assertSame('formatCurrency', $filters[0]->getName());
        self::assertSame('$12.00', call_user_func($filters[0]->getCallable(), 1200));
    }
}
