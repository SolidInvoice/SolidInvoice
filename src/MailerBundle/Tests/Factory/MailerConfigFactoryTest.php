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

namespace SolidInvoice\MailerBundle\Tests\Factory;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\MailerBundle\Configurator\SesConfigurator;
use SolidInvoice\MailerBundle\Factory\MailerConfigFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Transport;

class MailerConfigFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFromStrings(): void
    {
        $systemConfig = M::mock(SystemConfig::class);

        $factory = new MailerConfigFactory(new Transport(Transport::getDefaultFactories()), $systemConfig, [new SesConfigurator()]);

        $systemConfig->shouldReceive('get')
            ->with('email/sending_options/provider')
            ->andReturn('{"provider": "Amazon SES", "config": {"accessKey": "foobar", "accessSecret": "baz"}}');

        self::assertInstanceOf(SesApiAsyncAwsTransport::class, $factory->fromStrings());
    }

    public function testFromStringsWithNoConfigurators(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid mailer config');

        $systemConfig = M::mock(SystemConfig::class);

        $factory = new MailerConfigFactory(new Transport(Transport::getDefaultFactories()), $systemConfig, []);

        $systemConfig->shouldReceive('get')
            ->with('email/sending_options/provider')
            ->andReturn('{"provider": "Amazon SES", "config": {"accessKey": "foobar", "accessSecret": "baz"}}');

        $factory->fromStrings();
    }
}
