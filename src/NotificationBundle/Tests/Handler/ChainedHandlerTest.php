<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Tests\Handler;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Namshi\Notificator\ManagerInterface;
use Namshi\Notificator\Notification;
use SolidInvoice\NotificationBundle\Notification\ChainedNotification;
use SolidInvoice\NotificationBundle\Notification\Handler\ChainedHandler;
use PHPUnit\Framework\TestCase;
use SolidInvoice\NotificationBundle\Notification\SwiftMailerNotification;

class ChainedHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testShouldHandle()
    {
        $manager = M::mock(ManagerInterface::class);
        $handler = new ChainedHandler($manager);

        $this->assertTrue($handler->shouldHandle(new ChainedNotification()));
        $this->assertFalse($handler->shouldHandle(new Notification('Test')));
    }

    public function testHandle()
    {
        $manager = M::mock(ManagerInterface::class);
        $handler = new ChainedHandler($manager);
        $message1 = new Notification('test');
        $message2 = new SwiftMailerNotification('test2');

        $manager->shouldReceive('trigger')
            ->with($message1);

        $manager->shouldReceive('trigger')
            ->with($message2);

        $handler->handle(new ChainedNotification([$message1, $message2]));
    }
}
