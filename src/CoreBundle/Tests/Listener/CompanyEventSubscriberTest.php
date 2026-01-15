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
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Query\FilterCollection;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
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
use function date;
use function strtoupper;

/**
 * @covers \SolidInvoice\CoreBundle\Listener\CompanyEventSubscriber
 */
final class CompanyEventSubscriberTest extends TestCase
{
    public function testItRedirectsToCompanySelectPageIfACompanyIsNotSetAndUserHasMultipleCompanies(): void
    {
        // Test that it redirects to the company select page if a company is not set and the user has multiple companies

        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        $companySelector = new CompanySelector($this->createMock(ManagerRegistry::class));
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);

        $user = new User();
        $user->addCompany(new Company());
        $user->addCompany(new Company());

        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $router
            ->expects($this->once())
            ->method('generate')
            ->with('_select_company')
            ->willReturn('/select-company');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security, date('Y'));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertSame('/select-company', $event->getResponse()->getTargetUrl());
        self::assertNull($companySelector->getCompany());
    }

    public function testItSetsTheCompanyWhenNoCompanyIsSetAndTheUserOnlyHasOneCompany(): void
    {
        // Test that it redirects to the company select page if a company is not set and the user has multiple companies

        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        /** @var ManagerRegistry&MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);

        $companySelector = new CompanySelector($registry);

        $user = new User();
        $company = new Company();
        $user->addCompany($company);

        $this->setCompanyId($company, new Ulid());

        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $filter = $this->expectSwitchCompanyCalls($registry, $company);

        $router->expects($this->never())
            ->method('generate');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security, date('Y'));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
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

        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        $companySelector = new CompanySelector($this->createMock(ManagerRegistry::class));
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);

        $security->expects($this->never())
            ->method('getUser');

        $router
            ->expects($this->never())
            ->method('generate');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);
        $request->attributes->set('_route', $route);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security, date('Y'));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testItContinueExecutionWhenNoCompanyIsSetAndNoUserIsLoggedIn(): void
    {
        // Test that it continues execution when no company is set and no user is logged in

        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        $companySelector = new CompanySelector($this->createMock(ManagerRegistry::class));
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);

        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $router
            ->expects($this->never())
            ->method('generate');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security, date('Y'));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
        $listener->onKernelRequest($event);

        self::assertNull($event->getResponse());
    }

    public function testItSetsTheCompanyWhenItIsAvailableInTheSession(): void
    {
        /** @var RouterInterface&MockObject $router */
        $router = $this->createMock(RouterInterface::class);
        /** @var ManagerRegistry&MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        /** @var Security&MockObject $security */
        $security = $this->createMock(Security::class);

        $companySelector = new CompanySelector($registry);

        $security->expects($this->never())->method('getUser');

        $router->expects($this->never())->method('generate');

        $company = new Company();
        $this->setCompanyId($company, new Ulid());
        $filter = $this->expectSwitchCompanyCalls($registry, $company);

        $session = new Session(new MockArraySessionStorage());
        $session->set('company', $company->getId());
        $request = new Request();
        $request->setSession($session);

        $listener = new CompanyEventSubscriber($router, $companySelector, $security, date('Y'));

        $event = new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
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
     * @param ManagerRegistry&MockObject $registry
     */
    private function expectSwitchCompanyCalls($registry, Company $company): SQLFilter
    {
        /** @var FilterCollection&MockObject $filterCollection */
        $filterCollection = $this->createMock(FilterCollection::class);
        /** @var EntityManagerInterface&MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        /** @var Connection&MockObject $connection */
        $connection = $this->createMock(Connection::class);

        $filter = new class($em) extends SQLFilter {
            public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
            {
                return '';
            }
        };

        $registry
            ->expects($this->once())
            ->method('getManager')
            ->willReturn($em);

        $em
            ->expects($this->exactly(2))
            ->method('getFilters')
            ->willReturn($filterCollection);

        $em
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        $filterCollection
            ->expects($this->once())
            ->method('enable')
            ->with('company')
            ->willReturn($filter);

        $filterCollection
            ->expects($this->once())
            ->method('setFiltersStateDirty');

        $connection
            ->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(new SqlitePlatform());

        $connection
            ->expects($this->once())
            ->method('quote')
            ->with(strtoupper(substr($company->getId()->toHex(), 2)), 'string')
            ->willReturn($company->getId()->toHex());

        return $filter;
    }

    private function setCompanyId(Company $company, Ulid $id): void
    {
        $ref = new ReflectionClass($company);
        $property = $ref->getProperty('id');
        $property->setValue($company, $id);
    }
}
