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

namespace SolidInvoice\NotificationBundle\Tests;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Namshi\Notificator\NotificationInterface;
use PHPUnit\Framework\TestCase;
use SolidInvoice\NotificationBundle\Notification\ChainedNotification;

class ChainedNotificationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAddNotifications()
    {
        $notification = new ChainedNotification();
        $message1 = M::mock(NotificationInterface::class);
        $message2 = M::mock(NotificationInterface::class);
        $message3 = M::mock(NotificationInterface::class);
        $notification->addNotification($message1);
        $notification->addNotification($message2);
        $notification->addNotification($message3);

        self::assertSame([$message1, $message2, $message3], $notification->getNotifications());
    }

    public function testAddNotificationThroughConstructor()
    {
        $message1 = M::mock(NotificationInterface::class);
        $message2 = M::mock(NotificationInterface::class);
        $message3 = M::mock(NotificationInterface::class);
        $notification = new ChainedNotification([$message1, $message2, $message3]);

        self::assertSame([$message1, $message2, $message3], $notification->getNotifications());
    }
}
