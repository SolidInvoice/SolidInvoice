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

namespace SolidInvoice\CoreBundle\Tests\Logger;

use Carbon\CarbonImmutable;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Logger\ServerLogHandlerFactory;

final class ServerLogHandlerFactoryTest extends TestCase
{
    public function testItWritesJsonToStderrOutsideFrankenPhp(): void
    {
        $handler = ServerLogHandlerFactory::create('notice');

        self::assertInstanceOf(StreamHandler::class, $handler);
        self::assertSame('php://stderr', $handler->getUrl());
        self::assertSame(Level::Notice, $handler->getLevel());
        self::assertInstanceOf(JsonFormatter::class, $handler->getFormatter());
    }

    public function testItInterpolatesMessagePlaceholders(): void
    {
        $handler = ServerLogHandlerFactory::create('debug');
        $stream = fopen('php://memory', 'rb+');
        self::assertIsResource($stream);

        self::assertInstanceOf(StreamHandler::class, $handler);
        $output = new StreamHandler($stream);
        $output->setFormatter($handler->getFormatter());
        $output->pushProcessor($handler->popProcessor());

        $output->handle(new LogRecord(CarbonImmutable::now(), 'app', Level::Info, 'Stopped after {limit}s', ['limit' => 2]));

        rewind($stream);
        self::assertStringContainsString('"message":"Stopped after 2s"', (string) stream_get_contents($stream));
    }
}
