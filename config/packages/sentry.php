<?php

declare(strict_types=1);

use Symfony\Config\MonologConfig;
use Symfony\Config\SentryConfig;
use Sentry\State\HubInterface;
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
