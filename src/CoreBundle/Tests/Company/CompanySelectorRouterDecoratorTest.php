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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Company\CompanySelectorRouterDecorator;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Repository\CompanyRepository;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Uid\Ulid;

/**
 * @covers \SolidInvoice\CoreBundle\Company\CompanySelectorRouterDecorator
 */
final class CompanySelectorRouterDecoratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSwitchCompanyAppliesCustomDomainToContext(): void
    {
        $company = (new Company())->setCustomDomain('Acme.Example');
        $companyId = new Ulid();

        $registry = $this->stubRegistry($company, $companyId);
        $context = new RequestContext();

        $decorator = new CompanySelectorRouterDecorator($registry, $context, 'https://app.example.com');
        $decorator->switchCompany($companyId);

        self::assertSame('acme.example', $context->getHost());
        self::assertSame('https', $context->getScheme());
        self::assertSame(443, $context->getHttpsPort());
    }

    public function testSwitchCompanyFallsBackToApplicationUrlWhenNoCustomDomain(): void
    {
        $company = new Company();
        $companyId = new Ulid();

        $registry = $this->stubRegistry($company, $companyId);
        $context = new RequestContext();

        $decorator = new CompanySelectorRouterDecorator($registry, $context, 'https://app.example.com:8443');
        $decorator->switchCompany($companyId);

        self::assertSame('app.example.com', $context->getHost());
        self::assertSame('https', $context->getScheme());
        self::assertSame(8443, $context->getHttpsPort());
    }

    private function stubRegistry(Company $company, Ulid $companyId): ManagerRegistry
    {
        $registry = M::mock(ManagerRegistry::class);

        // For parent::switchCompany() — needs an EM with a connection / filters.
        $em = M::mock(EntityManagerInterface::class);
        $connection = M::mock(Connection::class);
        $connection->shouldReceive('getDatabasePlatform')
            ->zeroOrMoreTimes()
            ->andReturn(new SqlitePlatform());
        $connection->shouldReceive('quote')->zeroOrMoreTimes()->andReturn('xxx');
        $em->shouldReceive('getConnection')->zeroOrMoreTimes()->andReturn($connection);

        $filterCollection = M::mock(FilterCollection::class);
        $filter = new class($em) extends SQLFilter {
            public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
            {
                return '';
            }
        };
        $filterCollection->shouldReceive('enable')->zeroOrMoreTimes()->andReturn($filter);
        $filterCollection->shouldReceive('setFiltersStateDirty')->zeroOrMoreTimes();
        $em->shouldReceive('getFilters')->zeroOrMoreTimes()->andReturn($filterCollection);

        $registry->shouldReceive('getManager')->zeroOrMoreTimes()->andReturn($em);

        // For the decorator's company lookup.
        $companyRepository = M::mock(CompanyRepository::class);
        $companyRepository->shouldReceive('find')->with($companyId)->andReturn($company);
        $registry->shouldReceive('getRepository')
            ->with(Company::class)
            ->andReturn($companyRepository);

        return $registry;
    }
}
