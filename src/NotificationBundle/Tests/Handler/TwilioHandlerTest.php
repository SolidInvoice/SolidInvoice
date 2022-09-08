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

    public function testShouldHandle(): void
    {
        $client = M::mock(Client::class);
        $config = M::mock(SystemConfig::class);
        $handler = new TwilioHandler($client, $config);

        self::assertTrue($handler->shouldHandle(new TwilioNotification('1234567890', 'test')));
        self::assertFalse($handler->shouldHandle(new Notification('Test')));
    }

    public function testHandle(): void
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

    public function testHandleWithEmptyNumber(): void
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
