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

namespace CSBill\SettingsBundle\Tests\Twig\Extension;

use CSBill\SettingsBundle\Exception\InvalidSettingException;
use CSBill\SettingsBundle\SystemConfig;
use CSBill\SettingsBundle\Twig\Extension\SettingsExtension;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Mockery as M;

class SettingsExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetSettings()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('dummy/setting')
            ->andReturn(true);

        $extension = new SettingsExtension($config);

        $this->assertTrue($extension->getSetting('dummy/setting'));
    }

    public function testGetSettingsDefaultValue()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('dummy/setting')
            ->andThrow(new InvalidSettingException('dummy/setting'));

        $extension = new SettingsExtension($config);

        $this->assertFalse($extension->getSetting('dummy/setting', false));
    }
}
