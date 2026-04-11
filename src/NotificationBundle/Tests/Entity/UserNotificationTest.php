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

namespace SolidInvoice\NotificationBundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use SolidInvoice\NotificationBundle\Entity\TransportSetting;
use SolidInvoice\NotificationBundle\Entity\UserNotification;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Uid\Ulid;

/**
 * @covers \SolidInvoice\NotificationBundle\Entity\UserNotification
 */
final class UserNotificationTest extends TestCase
{
    public function testGetIdReturnsNullForNewEntity(): void
    {
        $notification = new UserNotification();

        self::assertNull($notification->getId());
    }

    public function testGetIdReturnNullBeforePersist(): void
    {
        $notification = new UserNotification();
        $notification->setEvent('invoice.paid');
        $notification->setEmail(true);

        // Accessing getId() on a new entity must not throw an error about
        // uninitialized typed property - it should return null instead.
        self::assertNull($notification->getId());
    }

    public function testSetAndGetEvent(): void
    {
        $notification = new UserNotification();
        $notification->setEvent('invoice.created');

        self::assertSame('invoice.created', $notification->getEvent());
    }

    public function testSetAndGetEmail(): void
    {
        $notification = new UserNotification();
        $notification->setEmail(true);

        self::assertTrue($notification->isEmail());

        $notification->setEmail(false);
        self::assertFalse($notification->isEmail());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $notification = new UserNotification();
        $notification->setUser($user);

        self::assertSame($user, $notification->getUser());
    }

    public function testTransportsDefaultToEmptyCollection(): void
    {
        $notification = new UserNotification();

        self::assertCount(0, $notification->getTransports());
    }

    public function testAddTransport(): void
    {
        $notification = new UserNotification();
        $transport = new TransportSetting();
        $transport->setName('Slack');
        $transport->setTransport('slack');

        $notification->addTransport($transport);

        self::assertCount(1, $notification->getTransports());
        self::assertTrue($notification->getTransports()->contains($transport));
    }

    public function testAddTransportDoesNotDuplicate(): void
    {
        $notification = new UserNotification();
        $transport = new TransportSetting();
        $transport->setName('Slack');
        $transport->setTransport('slack');

        $notification->addTransport($transport);
        $notification->addTransport($transport);

        self::assertCount(1, $notification->getTransports());
    }

    public function testRemoveTransport(): void
    {
        $notification = new UserNotification();
        $transport = new TransportSetting();
        $transport->setName('Slack');
        $transport->setTransport('slack');

        $notification->addTransport($transport);
        $notification->removeTransport($transport);

        self::assertCount(0, $notification->getTransports());
    }

    public function testToString(): void
    {
        $notification = new UserNotification();
        $notification->setEvent('invoice.sent');

        self::assertSame('invoice.sent', (string) $notification);
    }

    public function testIdIsNullableType(): void
    {
        $notification = new UserNotification();

        // Verify the return type is ?Ulid (nullable)
        $id = $notification->getId();
        self::assertNull($id);
        self::assertNotInstanceOf(Ulid::class, $id);
    }
}
