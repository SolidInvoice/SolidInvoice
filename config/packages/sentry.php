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

use Sentry\State\HubInterface;
use Symfony\Config\MonologConfig;
use Symfony\Config\SentryConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (SentryConfig $sentryConfig, MonologConfig $monologConfig): void {
    $sentryConfig->dsn(env('SENTRY_DSN'))
        ->registerErrorListener(false)
        ->registerErrorHandler(false)
        ->options([
            'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII')->bool(),
        ]);

    $monologConfig->handler('sentry_main')
        ->type('sentry')
        ->level(Monolog\Logger::ERROR)
        ->hubId(HubInterface::class);

    $monologConfig->handler('sentry')
        ->type('fingers_crossed')
        ->actionLevel(Monolog\Logger::ERROR)
        ->handler('sentry_main')
        ->excludedHttpCode(404)
        ->excludedHttpCode(405)
        ->bufferSize(50);
};
