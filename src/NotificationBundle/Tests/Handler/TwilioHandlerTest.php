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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use Namshi\Notificator\Notification;
use PHPUnit\Framework\TestCase;
use SolidInvoice\NotificationBundle\Notification\Handler\TwilioHandler;
use SolidInvoice\NotificationBundle\Notification\TwilioNotification;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twilio\Rest\Api\V2010\Account\MessageList;
use Twilio\Rest\Client;

class TwilioHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testShouldHandle()
    {
        $client = M::mock(Client::class);
        $config = M::mock(SystemConfig::class);
        $handler = new TwilioHandler($client, $config);

        $this->assertTrue($handler->shouldHandle(new TwilioNotification('1234567890', 'test')));
        $this->assertFalse($handler->shouldHandle(new Notification('Test')));
    }

    public function testHandle()
    {
        $client = M::mock(Client::class);
        $messageList = M::mock(MessageList::class);
        $client->messages = $messageList;
        $config = M::mock(SystemConfig::class);
        $handler = new TwilioHandler($client, $config);

        $config->shouldReceive('get')
            ->once()
            ->with('sms/twilio/number')
            ->andReturn('0987654321');

        $messageList->shouldReceive('create')
            ->once()
            ->with('1234567890', ['from' => '0987654321', 'body' => 'Test Message']);

        $handler->handle(new TwilioNotification('1234567890', 'Test Message'));
    }

    public function testHandleWithEmptyNumber()
    {
        $client = M::mock(Client::class);
        $messageList = M::mock(MessageList::class);
        $client->messages = $messageList;
        $config = M::mock(SystemConfig::class);
        $handler = new TwilioHandler($client, $config);

        $config->shouldReceive('get')
            ->once()
            ->with('sms/twilio/number')
            ->andReturn(null);

        $messageList->shouldReceive('create')
            ->never()
            ->with('1234567890', ['from' => '0987654321', 'body' => 'Test Message']);

        $handler->handle(new TwilioNotification('1234567890', 'Test Message'));
    }
}
