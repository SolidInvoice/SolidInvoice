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

namespace SolidInvoice\MailerBundle\Tests\Factory;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use SolidInvoice\CoreBundle\Mode\ModeResolver;
use SolidInvoice\MailerBundle\Configurator\SesConfigurator;
use SolidInvoice\MailerBundle\Factory\MailerConfigFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\NullTransport;
use Symfony\Component\Mailer\Transport\Transports;

final class MailerConfigFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testFromStrings(): void
    {
        $systemConfig = M::mock(SystemConfig::class);

        $factory = new MailerConfigFactory(new Transport(Transport::getDefaultFactories()), $systemConfig, [new SesConfigurator()], new ModeResolver());

        $systemConfig->shouldReceive('get')
            ->with('email/sending_options/provider')
            ->andReturn('{"provider": "Amazon SES", "config": {"accessKey": "foobar", "accessSecret": "baz"}}');

        self::assertInstanceOf(SesApiAsyncAwsTransport::class, $factory->fromStrings());
    }

    public function testFromStringsWithNoConfigurators(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid mailer config');

        $systemConfig = M::mock(SystemConfig::class);

        $factory = new MailerConfigFactory(new Transport(Transport::getDefaultFactories()), $systemConfig, [], new ModeResolver());

        $systemConfig->shouldReceive('get')
            ->with('email/sending_options/provider')
            ->andReturn('{"provider": "Amazon SES", "config": {"accessKey": "foobar", "accessSecret": "baz"}}');

        $factory->fromStrings();
    }

    public function testDemoModeIgnoresProviderConfigAndUsesEnvDsn(): void
    {
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $factory = new MailerConfigFactory(new Transport(Transport::getDefaultFactories()), $systemConfig, [new SesConfigurator()], new ModeResolver('demo'));

        $transport = $factory->fromStrings(['null://null']);

        // Symfony's Transport::fromStrings() always wraps the result in a Transports
        // composite (even for a single DSN), so the underlying transport is unwrapped
        // here via reflection to assert it is really the null transport.
        self::assertInstanceOf(Transports::class, $transport);

        $default = new ReflectionProperty(Transports::class, 'default');

        self::assertInstanceOf(NullTransport::class, $default->getValue($transport));
    }
}
