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

namespace SolidInvoice\CoreBundle\Tests\Listener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Listener\CompanyEventSubscriber;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Uid\Ulid;

/**
 * @covers \SolidInvoice\CoreBundle\Listener\CompanyEventSubscriber
 */
final class CompanyEventSubscriberTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItRedirectsToCompanySelectPageIfACompanyIsNotSetAndUserHasMultipleCompanies(): void
    {
        // Test that it redirects to the company select page if a company is not set and the user has multiple companies

        $router = M::mock(RouterInterface::class);
        $companySelector = new CompanySelector(M::mock(ManagerRegistry::class));
        $security = M::mock(Security::class);

        $user = new User();
        $user->addCompany(new Company());
        $user->addCompany(new Company());

        $security
            ->shouldReceive('getUser')
            ->andReturn($user);

        $router
            ->shouldReceive('generate')
            ->with('_select_company')
            ->once()
            ->andReturn('/select-company');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security);

        $event = new RequestEvent(M::mock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertSame('/select-company', $event->getResponse()->getTargetUrl());
        self::assertNull($companySelector->getCompany());
    }

    public function testItSetsTheCompanyWhenNoCompanyIsSetAndTheUserOnlyHasOneCompany(): void
    {
        // Test that it redirects to the company select page if a company is not set and the user has multiple companies

        $router = M::mock(RouterInterface::class);
        $registry = M::mock(ManagerRegistry::class);
        $security = M::mock(Security::class);

        $companySelector = new CompanySelector($registry);

        $user = new User();
        $company = new Company();
        $user->addCompany($company);

        $this->setCompanyId($company, new Ulid());

        $security
            ->shouldReceive('getUser')
            ->once()
            ->andReturn($user);

        $filter = $this->expectSwitchCompanyCalls($registry, $company);

        $router->shouldNotReceive('generate');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security);

        $event = new RequestEvent(M::mock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame($company->getId(), $companySelector->getCompany());
        self::assertSame($company->getId()->toHex(), $filter->getParameter('companyId'));
    }

    /**
     * @dataProvider provideCompanySelectionRoutes
     */
    public function testItContinueTheRequestWhenACompanyIsNotSetAndTheUserIsOnACompanySelectPage(string $route): void
    {
        // Test that it continues the request when a company is not set and the user is on a company select page

        $router = M::mock(RouterInterface::class);
        $companySelector = new CompanySelector(M::mock(ManagerRegistry::class));
        $security = M::mock(Security::class);

        $security->shouldNotReceive('getUser');

        $router
            ->shouldNotReceive('generate');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_route', $route);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security);

        $event = new RequestEvent(M::mock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testItContinueExecutionWhenNoCompanyIsSetAndNoUserIsLoggedIn(): void
    {
        // Test that it continues execution when no company is set and no user is logged in

        $router = M::mock(RouterInterface::class);
        $companySelector = new CompanySelector(M::mock(ManagerRegistry::class));
        $security = M::mock(Security::class);

        $security
            ->shouldReceive('getUser')
            ->once()
            ->andReturn(null);

        $router
            ->shouldNotReceive('generate');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security);

        $event = new RequestEvent(M::mock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testItSetsTheCompanyWhenItIsAvailableInTheSession(): void
    {
        $router = M::mock(RouterInterface::class);
        $registry = M::mock(ManagerRegistry::class);
        $security = M::mock(Security::class);

        $companySelector = new CompanySelector($registry);

        $security->shouldNotReceive('getUser');

        $router->shouldNotReceive('generate');

        $company = new Company();
        $this->setCompanyId($company, new Ulid());
        $filter = $this->expectSwitchCompanyCalls($registry, $company);

        $session = new Session(new MockArraySessionStorage());
        $session->set('company', $company->getId());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security);

        $event = new RequestEvent(M::mock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
        self::assertSame($company->getId(), $companySelector->getCompany());
        self::assertSame($company->getId()->toHex(), $filter->getParameter('companyId'));
    }

    /**
     * @return iterable<array<string>>
     */
    public static function provideCompanySelectionRoutes(): iterable
    {
        yield ['_select_company'];
        yield ['_switch_company'];
        yield ['_create_company'];
    }

    /**
     * @param ManagerRegistry&M\MockInterface $registry
     */
    private function expectSwitchCompanyCalls($registry, Company $company): SQLFilter
    {
        $filterCollection = M::mock(FilterCollection::class);
        $em = M::mock(EntityManagerInterface::class);
        $connection = M::mock(Connection::class);

        $filter = new class($em) extends SQLFilter {
            public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
            {
                return '';
            }
        };

        $registry
            ->shouldReceive('getManager')
            ->once()
            ->andReturn($em);

        $em
            ->shouldReceive('getFilters')
            ->twice()
            ->andReturn($filterCollection);

        $em
            ->shouldReceive('getConnection')
            ->once()
            ->andReturn($connection);

        $filterCollection
            ->shouldReceive('enable')
            ->once()
            ->with('company')
            ->andReturn($filter);

        $filterCollection
            ->shouldReceive('setFiltersStateDirty')
            ->once()
            ->withNoArgs();

        $connection
            ->shouldReceive('quote')
            ->once()
            ->with($company->getId()->toHex(), Types::STRING)
            ->andReturn($company->getId()->toHex());

        return $filter;
    }

    private function setCompanyId(Company $company, Ulid $id): void
    {
        $ref = new ReflectionClass($company);
        $property = $ref->getProperty('id');
        $property->setValue($company, $id);
    }
}
