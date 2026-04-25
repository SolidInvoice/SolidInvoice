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

namespace SolidInvoice\CoreBundle\Tests\Company;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanyDomainResolver;
use SolidInvoice\CoreBundle\Company\HostType;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;

/**
 * @covers \SolidInvoice\CoreBundle\Company\CompanyDomainResolver
 */
final class CompanyDomainResolverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testReturnsDefaultHostWhenApplicationUrlEmpty(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldNotReceive('findOneByCustomDomain');

        $resolver = new CompanyDomainResolver($repository, '');

        $resolved = $resolver->resolve('whatever.example');

        self::assertSame(HostType::DefaultHost, $resolved->type);
    }

    public function testMatchesDefaultHost(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldNotReceive('findOneByCustomDomain');

        $resolver = new CompanyDomainResolver($repository, 'https://app.example.com');

        $resolved = $resolver->resolve('app.example.com');

        self::assertTrue($resolved->isDefaultHost());
        self::assertSame('app.example.com', $resolved->host);
        self::assertSame('https', $resolved->scheme);
    }

    public function testResolvesCustomDomainCompany(): void
    {
        $company = new Company();
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldReceive('findOneByCustomDomain')
            ->once()
            ->with('acme.example')
            ->andReturn($company);

        $resolver = new CompanyDomainResolver($repository, 'https://app.example.com');

        $resolved = $resolver->resolve('Acme.Example.');

        self::assertTrue($resolved->isCustomDomain());
        self::assertSame('acme.example', $resolved->host);
        self::assertSame('https', $resolved->scheme);
        self::assertSame(443, $resolved->port);
        self::assertSame($company, $resolved->company);
    }

    public function testReturnsUnknownWhenHostNotFound(): void
    {
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldReceive('findOneByCustomDomain')
            ->once()
            ->with('rogue.example')
            ->andReturnNull();

        $resolver = new CompanyDomainResolver($repository, 'https://app.example.com');

        $resolved = $resolver->resolve('rogue.example');

        self::assertSame(HostType::Unknown, $resolved->type);
    }

    public function testCachesResolutionPerHost(): void
    {
        $company = new Company();
        $repository = M::mock(CompanyRepository::class);
        $repository->shouldReceive('findOneByCustomDomain')
            ->once()
            ->andReturn($company);

        $resolver = new CompanyDomainResolver($repository, 'https://app.example.com');

        $first = $resolver->resolve('acme.example');
        $second = $resolver->resolve('acme.example');

        self::assertSame($first, $second);
    }
}
