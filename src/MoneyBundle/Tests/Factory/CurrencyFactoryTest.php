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

namespace SolidInvoice\MoneyBundle\Tests\Factory;

use Carbon\Carbon;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MoneyBundle\Factory\CurrencyFactory;
use SolidInvoice\SettingsBundle\SystemConfig;

class CurrencyFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDefaultCurrency(): void
    {
        $config = M::mock(SystemConfig::class);
        $factory = new CurrencyFactory(null, $config);

        self::assertEquals(new Currency('USD'), $factory->getCurrency());

        $config->shouldNotHaveReceived('get');
    }

    public function testCurrency(): void
    {
        $config = M::mock(SystemConfig::class);

        $config->shouldReceive('get')
            ->with(CurrencyFactory::CURRENCY_PATH)
            ->andReturn('EUR');

        $factory = new CurrencyFactory((string) Carbon::now(), $config);

        self::assertEquals(new Currency('EUR'), $factory->getCurrency());
    }
}
