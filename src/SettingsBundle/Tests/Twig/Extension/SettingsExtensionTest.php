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

namespace SolidInvoice\SettingsBundle\Tests\Twig\Extension;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\SettingsBundle\Exception\InvalidSettingException;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\SettingsBundle\Twig\Extension\SettingsExtension;

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

        self::assertTrue($extension->getSetting('dummy/setting'));
    }

    public function testGetSettingsDefaultValue()
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('dummy/setting')
            ->andThrow(new InvalidSettingException('dummy/setting'));

        $extension = new SettingsExtension($config);

        self::assertFalse($extension->getSetting('dummy/setting', false));
    }
}
