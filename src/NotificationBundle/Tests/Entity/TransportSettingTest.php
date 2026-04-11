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
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Uid\Ulid;

/**
 * @covers \SolidInvoice\NotificationBundle\Entity\TransportSetting
 */
final class TransportSettingTest extends TestCase
{
    public function testGetIdReturnsNullForNewEntity(): void
    {
        $setting = new TransportSetting();

        self::assertNull($setting->getId());
    }

    public function testGetIdReturnNullBeforePersist(): void
    {
        $setting = new TransportSetting();
        $setting->setName('Test');
        $setting->setTransport('some_transport');

        // Accessing getId() on a new entity must not throw an error about
        // uninitialized typed property - it should return null instead.
        self::assertNull($setting->getId());
    }

    public function testSetAndGetName(): void
    {
        $setting = new TransportSetting();
        $setting->setName('My Transport');

        self::assertSame('My Transport', $setting->getName());
    }

    public function testSetAndGetTransport(): void
    {
        $setting = new TransportSetting();
        $setting->setTransport('slack');

        self::assertSame('slack', $setting->getTransport());
    }

    public function testSetAndGetSettings(): void
    {
        $setting = new TransportSetting();
        $config = ['token' => 'abc123', 'channel' => '#general'];
        $setting->setSettings($config);

        self::assertSame($config, $setting->getSettings());
    }

    public function testDefaultSettingsAreEmpty(): void
    {
        $setting = new TransportSetting();

        self::assertSame([], $setting->getSettings());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $setting = new TransportSetting();
        $setting->setUser($user);

        self::assertSame($user, $setting->getUser());
    }

    public function testToString(): void
    {
        $setting = new TransportSetting();
        $setting->setName('Slack Notifications');

        self::assertSame('Slack Notifications', (string) $setting);
    }

    public function testIdIsNullableType(): void
    {
        $setting = new TransportSetting();

        // Verify the return type is ?Ulid (nullable)
        $id = $setting->getId();
        self::assertNull($id);
        self::assertNotInstanceOf(Ulid::class, $id);
    }
}
