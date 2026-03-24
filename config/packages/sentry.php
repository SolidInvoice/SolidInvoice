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

use Monolog\Level;
use Sentry\SentryBundle\Monolog\LogsHandler;
use Sentry\State\HubInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\ErrorHandler\Error\FatalError;
use Symfony\Config\MonologConfig;
use Symfony\Config\SentryConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (ContainerConfigurator $container, SentryConfig $sentryConfig, MonologConfig $monologConfig): void {
    $sentryConfig->dsn(env('SOLIDINVOICE_SENTRY_DSN'))
        ->registerErrorListener(false)
        ->registerErrorHandler(false)
        ->options()
        ->sendDefaultPii(env('SOLIDINVOICE_SENTRY_SEND_DEFAULT_PII')->bool())
        ->ignoreExceptions([FatalError::class])
        ->release(env('SOLIDINVOICE_SENTRY_RELEASE')->default('application_version'))
        ->enableLogs(true)
        // Tracing: set SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE to a value between 0.0 and 1.0 to enable.
        // 0.0 = no transactions captured, 1.0 = 100% captured. Start low in production (e.g. 0.1).
        // Recommended: 0.1 for medium traffic, 0.01 for high traffic.
        ->tracesSampleRate(env('SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE')->float())
        // Profiling: requires the excimer PHP extension (see build-static.sh).
        // profiles_sample_rate is relative to traces_sample_rate: if traces=0.1 and profiles=1.0,
        // 10% of requests are traced and 100% of those traces are profiled.
        // Only set > 0 when excimer is installed; otherwise leave at 0.
        ->profilesSampleRate(env('SOLIDINVOICE_SENTRY_PROFILES_SAMPLE_RATE')->float())
        // HTTP timeouts in seconds. Lower values (e.g. 1-2s) are safe when using a local Relay proxy,
        // which responds near-instantly and forwards asynchronously to sentry.io.
        // When sending directly to sentry.io, consider 5-10s to tolerate occasional latency.
        // To use Relay: change SOLIDINVOICE_SENTRY_DSN to point to your Relay instance,
        // e.g. http://<key>@localhost:3000/<project-id>, and keep timeouts at 2s.
        ->httpTimeout(env('SOLIDINVOICE_SENTRY_HTTP_TIMEOUT')->float())
        ->httpConnectTimeout(env('SOLIDINVOICE_SENTRY_HTTP_CONNECT_TIMEOUT')->float())
        // Ignore noisy internal/infrastructure transactions that add volume without insight.
        ->ignoreTransactions(['GET /_fragment']);

    // Symfony-specific tracing integrations. These register lightweight service decorators
    // unconditionally — even when traces_sample_rate=0. The overhead is a single null-check
    // per operation (no active span → immediate return), which is negligible in practice.
    // All integrations are pre-wired so that tracing can be enabled purely via the env var
    // without a redeploy or config change.
    $sentryConfig->tracing()
        ->enabled(true)
        ->dbal()        // Traces every Doctrine SQL query as a child span
        ->enabled(true);
    $sentryConfig->tracing()
        ->twig()        // Traces Twig template renders
        ->enabled(true);
    $sentryConfig->tracing()
        ->cache()       // Traces Symfony Cache hits and misses
        ->enabled(true);
    $sentryConfig->tracing()
        ->httpClient()  // Traces outgoing Symfony HttpClient requests
        ->enabled(true);
    $sentryConfig->tracing()
        ->console()
            // Long-running workers must be excluded: they would create a single trace that
            // spans the entire worker lifetime rather than per-message traces.
        ->excludedCommands(['messenger:consume', 'schedule:run', 'cron:run']);

    $container->services()
        ->set(LogsHandler::class)
        ->args([Level::Info]);

    $monologConfig->handler('sentry_main')
        ->type('sentry')
        ->level(Level::Error->value)
        ->hubId(HubInterface::class);

    $monologConfig->handler('sentry')
        ->type('fingers_crossed')
        ->actionLevel(Level::Error->value)
        ->handler('sentry_main')
        ->excludedHttpCode(401)
        ->excludedHttpCode(404)
        ->excludedHttpCode(405)
        ->bufferSize(50);

    $monologConfig->handler('sentry_logs')
        ->type('service')
        ->id(LogsHandler::class)
        ->channels()->elements(['!doctrine', '!request', '!security', '!event', '!console']);
};
