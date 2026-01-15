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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SolidInvoice\MailerBundle\Configurator\SesConfigurator;
use SolidInvoice\MailerBundle\Factory\MailerConfigFactory;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Transport;

class MailerConfigFactoryTest extends TestCase
{
    public function testFromStrings(): void
    {
        /** @var SystemConfig&MockObject $systemConfig */
        $systemConfig = $this->createMock(SystemConfig::class);

        $factory = new MailerConfigFactory(new Transport(Transport::getDefaultFactories()), $systemConfig, [new SesConfigurator()]);

        $systemConfig->method('get')
            ->with('email/sending_options/provider')
            ->willReturn('{"provider": "Amazon SES", "config": {"accessKey": "foobar", "accessSecret": "baz"}}');

        self::assertInstanceOf(SesApiAsyncAwsTransport::class, $factory->fromStrings());
    }

    public function testFromStringsWithNoConfigurators(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid mailer config');

        /** @var SystemConfig&MockObject $systemConfig */
        $systemConfig = $this->createMock(SystemConfig::class);

        $factory = new MailerConfigFactory(new Transport(Transport::getDefaultFactories()), $systemConfig, []);

        $systemConfig->method('get')
            ->with('email/sending_options/provider')
            ->willReturn('{"provider": "Amazon SES", "config": {"accessKey": "foobar", "accessSecret": "baz"}}');

        $factory->fromStrings();
    }
}
