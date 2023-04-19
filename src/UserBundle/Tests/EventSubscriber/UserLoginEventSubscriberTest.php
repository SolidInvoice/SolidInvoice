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
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\EventSubscriber\UserLoginEventSubscriber;
use SolidInvoice\UserBundle\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/** @covers \SolidInvoice\UserBundle\EventSubscriber\UserLoginEventSubscriber */
final class UserLoginEventSubscriberTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testGetSubscribedEvents(): void
    {
        self::assertSame([InteractiveLoginEvent::class => 'onLogin'], UserLoginEventSubscriber::getSubscribedEvents());
    }

    public function testOnLogin(): void
    {
        $entityManager = M::mock(EntityManagerInterface::class);
        $userRepository = M::mock(UserRepository::class);
        $user = new User();

        $entityManager->expects('getRepository')
            ->with(User::class)
            ->andReturn($userRepository);

        $userRepository
            ->expects('save')
            ->once()
            ->with($user);

        $subscriber = new UserLoginEventSubscriber($entityManager);

        $subscriber->onLogin(new InteractiveLoginEvent(new Request(), new UsernamePasswordToken($user, 'main')));

        self::assertInstanceOf(DateTimeImmutable::class, $user->getLastLogin());
    }
}
