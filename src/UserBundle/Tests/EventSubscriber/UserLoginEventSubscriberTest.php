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

namespace SolidInvoice\UserBundle\Tests\EventSubscriber;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Company\HostType;
use SolidInvoice\CoreBundle\Company\ResolvedHost;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Listener\HostRoutingListener;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\EventSubscriber\UserLoginEventSubscriber;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Uid\Ulid;

/** @covers \SolidInvoice\UserBundle\EventSubscriber\UserLoginEventSubscriber */
final class UserLoginEventSubscriberTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGetSubscribedEvents(): void
    {
        self::assertSame([
            LoginSuccessEvent::class => 'onLogin',
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ], UserLoginEventSubscriber::getSubscribedEvents());
    }

    public function testOnLogin(): void
    {
        $entityManager = M::mock(EntityManagerInterface::class);
        $userRepository = M::mock(UserRepository::class);
        $loginEvent = M::mock(LoginSuccessEvent::class);
        $user = new User();

        $loginEvent
            ->shouldReceive('getUser')
            ->andReturn($user);

        $entityManager->expects('getRepository')
            ->with(User::class)
            ->andReturn($userRepository);

        $userRepository
            ->expects('save')
            ->once()
            ->with($user);

        $subscriber = new UserLoginEventSubscriber($entityManager, new RequestStack());

        $subscriber->onLogin($loginEvent);

        self::assertInstanceOf(DateTimeImmutable::class, $user->getLastLogin());
    }

    public function testOnAuthenticationSuccessRejectsUserNotInCustomDomainCompany(): void
    {
        $entityManager = M::mock(EntityManagerInterface::class);

        $user = new User();
        $user->setVerified(true);

        $domainCompany = new Company();
        $this->assignCompanyId($domainCompany, new Ulid());

        $token = M::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $event = new AuthenticationSuccessEvent($token);

        $request = new Request();
        $request->attributes->set(
            HostRoutingListener::REQUEST_ATTR,
            new ResolvedHost(HostType::CustomDomain, 'acme.example', 'https', 443, $domainCompany)
        );

        $stack = new RequestStack();
        $stack->push($request);

        $subscriber = new UserLoginEventSubscriber($entityManager, $stack);

        $this->expectException(BadCredentialsException::class);

        $subscriber->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessAllowsUserInCustomDomainCompany(): void
    {
        $entityManager = M::mock(EntityManagerInterface::class);

        $domainCompany = new Company();
        $this->assignCompanyId($domainCompany, new Ulid());

        $user = new User();
        $user->setVerified(true);
        $user->addCompany($domainCompany);

        $token = M::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $event = new AuthenticationSuccessEvent($token);

        $request = new Request();
        $request->attributes->set(
            HostRoutingListener::REQUEST_ATTR,
            new ResolvedHost(HostType::CustomDomain, 'acme.example', 'https', 443, $domainCompany)
        );

        $stack = new RequestStack();
        $stack->push($request);

        $subscriber = new UserLoginEventSubscriber($entityManager, $stack);

        $this->expectNotToPerformAssertions();

        $subscriber->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessRejectsCustomDomainWithNullCompany(): void
    {
        $entityManager = M::mock(EntityManagerInterface::class);

        $user = new User();
        $user->setVerified(true);

        $token = M::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        $event = new AuthenticationSuccessEvent($token);

        $request = new Request();
        $request->attributes->set(
            HostRoutingListener::REQUEST_ATTR,
            new ResolvedHost(HostType::CustomDomain, 'acme.example', 'https', 443, null)
        );

        $stack = new RequestStack();
        $stack->push($request);

        $subscriber = new UserLoginEventSubscriber($entityManager, $stack);

        $this->expectException(BadCredentialsException::class);

        $subscriber->onAuthenticationSuccess($event);
    }

    private function assignCompanyId(Company $company, Ulid $id): void
    {
        $property = new ReflectionProperty($company, 'id');
        $property->setValue($company, $id);
    }
}
