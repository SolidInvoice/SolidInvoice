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

namespace SolidInvoice\CoreBundle\Logger;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FrankenPhpHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use function function_exists;

/**
 * Web requests served by FrankenPHP log through Caddy's logger, so they share its
 * JSON format and levels. Console commands and workers have no frankenphp_log()
 * and write JSON lines to stderr instead, which the launcher maps onto Caddy's log.
 *
 * Decided per process, not at compile time: cache:clear compiles the container from the CLI.
 *
 * @see \SolidInvoice\CoreBundle\Tests\Logger\ServerLogHandlerFactoryTest
 */
final class ServerLogHandlerFactory
{
    public static function create(string $level): HandlerInterface & ProcessableHandlerInterface
    {
        if (function_exists('frankenphp_log')) {
            $handler = new FrankenPhpHandler($level);
        } else {
            $handler = new StreamHandler('php://stderr', $level);
            $handler->setFormatter(new JsonFormatter());
        }

        // Monolog-bundle only adds this to handlers it builds itself, not to "service" handlers
        $handler->pushProcessor(new PsrLogMessageProcessor());

        return $handler;
    }
}
