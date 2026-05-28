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

namespace SolidInvoice\CoreBundle\Tests\Twig\Extension;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Twig\Extension\TemplateSettingsExtension;
use SolidInvoice\SettingsBundle\SystemConfig;

final class TemplateSettingsExtensionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testReturnsValueForWhitelistedKey(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('system/company/company_name')
            ->andReturn('Acme Inc');

        $extension = new TemplateSettingsExtension($config);

        self::assertSame('Acme Inc', $extension->getSetting('system/company/company_name'));
    }

    public function testReturnsDefaultForNonWhitelistedKey(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldNotReceive('get');

        $extension = new TemplateSettingsExtension($config);

        self::assertNull($extension->getSetting('database/password'));
        self::assertSame('default', $extension->getSetting('email/sending_options/provider', 'default'));
    }

    public function testReturnsDefaultForNullValue(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('system/company/vat_number')
            ->andReturn(null);

        $extension = new TemplateSettingsExtension($config);

        self::assertSame('-', $extension->getSetting('system/company/vat_number', '-'));
    }

    public function testJsonDecodesValueWhenRequested(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('system/company/contact_details/address')
            ->andReturn('{"city":"Berlin"}');

        $extension = new TemplateSettingsExtension($config);

        self::assertSame(['city' => 'Berlin'], $extension->getSetting('system/company/contact_details/address', null, true));
    }

    public function testRenderAddressHandlesNull(): void
    {
        $config = M::mock(SystemConfig::class);

        $extension = new TemplateSettingsExtension($config);

        self::assertSame('', $extension->renderAddress(null));
    }
}
