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

namespace SolidInvoice\NotificationBundle\Tests\Attribute;

use PHPUnit\Framework\TestCase;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;

/**
 * @covers \SolidInvoice\NotificationBundle\Attribute\AsNotification
 */
final class AsNotificationTest extends TestCase
{
    public function testAsNotificationWithAllParameters(): void
    {
        $attribute = new AsNotification(
            name: 'test_event',
            title: 'Test Event',
            description: 'This is a test event',
            icon: 'tabler:test',
            category: NotificationCategory::CLIENT,
        );

        self::assertSame('test_event', $attribute->name);
        self::assertSame('Test Event', $attribute->title);
        self::assertSame('This is a test event', $attribute->description);
        self::assertSame('tabler:test', $attribute->icon);
        self::assertSame(NotificationCategory::CLIENT, $attribute->category);
    }

    public function testAsNotificationWithDefaultParameters(): void
    {
        $attribute = new AsNotification(name: 'test_event');

        self::assertSame('test_event', $attribute->name);
        self::assertSame('', $attribute->title);
        self::assertSame('', $attribute->description);
        self::assertSame('tabler:bell', $attribute->icon);
        self::assertSame(NotificationCategory::OTHER, $attribute->category);
    }

    public function testAsNotificationWithPartialParameters(): void
    {
        $attribute = new AsNotification(
            name: 'test_event',
            title: 'Test Event',
            category: NotificationCategory::INVOICE,
        );

        self::assertSame('test_event', $attribute->name);
        self::assertSame('Test Event', $attribute->title);
        self::assertSame('', $attribute->description);
        self::assertSame('tabler:bell', $attribute->icon);
        self::assertSame(NotificationCategory::INVOICE, $attribute->category);
    }
}
