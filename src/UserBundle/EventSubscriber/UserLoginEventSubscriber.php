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

namespace SolidInvoice\UserBundle\EventSubscriber;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Exception\UserNotVerifiedException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @see \SolidInvoice\UserBundle\Tests\EventSubscriber\UserLoginEventSubscriberTest
 */
final class UserLoginEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLogin',
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        assert($user instanceof User);

        if (! $user->isVerified()) {
            throw new UserNotVerifiedException();
        }
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        assert($user instanceof User);

        $user->setLastLogin(new DateTimeImmutable());

        $this->entityManager->getRepository(User::class)->save($user);
    }
}
