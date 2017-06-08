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

namespace CSBill\MoneyBundle\Tests\Factory;

use Carbon\Carbon;
use CSBill\MoneyBundle\Factory\CurrencyFactory;
use CSBill\SettingsBundle\SystemConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Money\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testDefaultCurrency()
    {
        $config = M::mock(SystemConfig::class);
        $factory = new CurrencyFactory(null, $config);

        $this->assertEquals(new Currency('USD'), $factory->getCurrency());

        $config->shouldNotHaveReceived('get');
    }

    public function testCurrency()
    {
        $config = M::mock(SystemConfig::class);

        $config->shouldReceive('get')
            ->with('system/general/currency')
            ->andReturn('EUR');

        $factory = new CurrencyFactory((string) Carbon::now(), $config);

        $this->assertEquals(new Currency('EUR'), $factory->getCurrency());
    }
}
