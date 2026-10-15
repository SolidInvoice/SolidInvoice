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
use RuntimeException;
use SolidInvoice\CoreBundle\Company\CompanySelectorInterface;
use SolidInvoice\MailerBundle\Configurator\SesConfigurator;
use SolidInvoice\MailerBundle\Factory\MailerConfigFactory;
use SolidInvoice\MailerBundle\Transport\CompanyTransport;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Transports;
use Symfony\Component\Uid\Ulid;
use function iterator_to_array;

final class MailerConfigFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const string SES_CONFIG = '{"provider": "Amazon SES", "config": {"accessKey": "foobar", "accessSecret": "baz"}}';

    public function testFromStringsReturnsACompanyTransport(): void
    {
        $factory = new MailerConfigFactory(new Transport(iterator_to_array(Transport::getDefaultFactories())), M::mock(SystemConfig::class), [], $this->companySelector(new Ulid()));

        self::assertInstanceOf(CompanyTransport::class, $factory->fromStrings(['null://null']));
    }

    public function testResolveUsesTheCompanyProvider(): void
    {
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldReceive('get')
            ->with('email/sending_options/provider')
            ->andReturn(self::SES_CONFIG);

        $factory = new MailerConfigFactory(new Transport(iterator_to_array(Transport::getDefaultFactories())), $systemConfig, [new SesConfigurator()], $this->companySelector(new Ulid()));

        self::assertInstanceOf(SesApiAsyncAwsTransport::class, $factory->resolve());
    }

    public function testResolveIgnoresCompanySettingsWithoutACompany(): void
    {
        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldNotReceive('get');

        $factory = new MailerConfigFactory(new Transport(iterator_to_array(Transport::getDefaultFactories())), $systemConfig, [new SesConfigurator()], $this->companySelector(null));

        $transport = $factory->resolve(['null://null']);

        self::assertInstanceOf(Transports::class, $transport);
    }

    public function testResolveFollowsTheActiveCompany(): void
    {
        $companyA = new Ulid();
        $companyB = new Ulid();
        $active = $companyA;

        $companySelector = M::mock(CompanySelectorInterface::class);
        $companySelector->shouldReceive('getCompany')->andReturnUsing(static function () use (&$active): Ulid {
            return $active;
        });

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldReceive('get')
            ->with('email/sending_options/provider')
            ->andReturnUsing(static function () use (&$active, $companyA): ?string {
                return $active === $companyA ? self::SES_CONFIG : null;
            });

        $factory = new MailerConfigFactory(new Transport(iterator_to_array(Transport::getDefaultFactories())), $systemConfig, [new SesConfigurator()], $companySelector);

        $first = $factory->resolve(['null://null']);

        $active = $companyB;
        $second = $factory->resolve(['null://null']);

        $active = $companyA;
        $third = $factory->resolve(['null://null']);

        self::assertInstanceOf(SesApiAsyncAwsTransport::class, $first);
        self::assertInstanceOf(Transports::class, $second);
        self::assertSame($first, $third);
    }

    public function testResolveWithNoConfigurators(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Invalid mailer config');

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->shouldReceive('get')
            ->with('email/sending_options/provider')
            ->andReturn(self::SES_CONFIG);

        $factory = new MailerConfigFactory(new Transport(iterator_to_array(Transport::getDefaultFactories())), $systemConfig, [], $this->companySelector(new Ulid()));

        $factory->resolve();
    }

    private function companySelector(?Ulid $company): CompanySelectorInterface
    {
        $selector = M::mock(CompanySelectorInterface::class);
        $selector->shouldReceive('getCompany')->andReturn($company);

        return $selector;
    }
}
