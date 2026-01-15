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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\EventSubscriber\UserLoginEventSubscriber;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/** @covers \SolidInvoice\UserBundle\EventSubscriber\UserLoginEventSubscriber */
final class UserLoginEventSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        self::assertSame([
            LoginSuccessEvent::class => 'onLogin',
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ], UserLoginEventSubscriber::getSubscribedEvents());
    }

    public function testOnLogin(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        /** @var UserRepository&MockObject $userRepository */
        $userRepository = $this->createMock(UserRepository::class);
        /** @var LoginSuccessEvent&MockObject $loginEvent */
        $loginEvent = $this->createMock(LoginSuccessEvent::class);
        $user = new User();

        $loginEvent
            ->method('getUser')
            ->willReturn($user);

        $entityManager->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $userRepository
            ->expects($this->once())
            ->method('save')
            ->with($user);

        $subscriber = new UserLoginEventSubscriber($entityManager);

        $subscriber->onLogin($loginEvent);

        self::assertInstanceOf(DateTimeImmutable::class, $user->getLastLogin());
    }
}
