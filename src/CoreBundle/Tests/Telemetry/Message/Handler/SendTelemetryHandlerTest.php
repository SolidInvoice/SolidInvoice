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

namespace SolidInvoice\CoreBundle\Tests\Telemetry\Message\Handler;

use const JSON_THROW_ON_ERROR;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SolidInvoice\CoreBundle\Telemetry\Message\Handler\SendTelemetryHandler;
use SolidInvoice\CoreBundle\Telemetry\Message\SendTelemetryMessage;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use function json_decode;

#[CoversClass(SendTelemetryHandler::class)]
final class SendTelemetryHandlerTest extends TestCase
{
    public function testItPostsPingToTheCorrectUrl(): void
    {
        $requests = [];

        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url, 'options' => $options];

            return new MockResponse('', ['http_code' => 200]);
        });

        $handler = new SendTelemetryHandler($client, new NullLogger(), 'https://insights.solidworx.co');

        $handler(new SendTelemetryMessage('ping', ['build_id' => 'abc', 'app' => 'solidinvoice']));

        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]['method']);
        self::assertSame('https://insights.solidworx.co/v1/ping', $requests[0]['url']);
        self::assertContains('Content-Type: application/json', $requests[0]['options']['headers']);
        self::assertSame(
            ['build_id' => 'abc', 'app' => 'solidinvoice'],
            json_decode((string) $requests[0]['options']['body'], true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testItPostsEventToTheCorrectUrl(): void
    {
        $requests = [];

        $client = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = ['method' => $method, 'url' => $url];

            return new MockResponse('', ['http_code' => 202]);
        });

        $handler = new SendTelemetryHandler($client, new NullLogger(), 'https://insights.solidworx.co/');

        $handler(new SendTelemetryMessage('event', ['build_id' => 'abc', 'app' => 'solidinvoice', 'event' => 'invoice_created']));

        self::assertCount(1, $requests);
        self::assertSame('https://insights.solidworx.co/v1/event', $requests[0]['url']);
    }

    public function testItSwallowsTransportErrors(): void
    {
        // An `error` in the response info makes MockHttpClient throw a
        // TransportException as soon as the status code is read.
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse('', ['error' => 'Connection refused']));

        $handler = new SendTelemetryHandler($client, new NullLogger(), 'https://unreachable.invalid');

        // Must not throw — the message acks so it is never retried or moved to the failed queue.
        $handler(new SendTelemetryMessage('ping', ['build_id' => 'abc']));

        $this->expectNotToPerformAssertions();
    }

    public function testItSwallowsNonSuccessStatusCodes(): void
    {
        $client = new MockHttpClient(static fn (): MockResponse => new MockResponse('error', ['http_code' => 500]));

        $handler = new SendTelemetryHandler($client, new NullLogger(), 'https://insights.solidworx.co');

        // A non-2xx response must be swallowed without throwing.
        $handler(new SendTelemetryMessage('event', ['build_id' => 'abc']));

        $this->expectNotToPerformAssertions();
    }
}
