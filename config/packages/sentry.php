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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Monolog\Level;
use Sentry\Monolog\Handler;
use Sentry\SentryBundle\Monolog\LogsHandler;
use Sentry\State\HubInterface;
use Symfony\Component\ErrorHandler\Error\FatalError;

return App::config([
    'sentry' => [
        'dsn' => env('SOLIDINVOICE_SENTRY_DSN'),
        'register_error_listener' => false,
        'register_error_handler' => false,
        'options' => [
            'send_default_pii' => env('SOLIDINVOICE_SENTRY_SEND_DEFAULT_PII')
                ->bool(),
            'ignore_exceptions' => [FatalError::class],
            'release' => env('SOLIDINVOICE_SENTRY_RELEASE')
                ->default('application_version'),
            'enable_logs' => true,
            // Tracing: set SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE to a value between 0.0 and 1.0 to enable.
            // 0.0 = no transactions captured, 1.0 = 100% captured. Start low in production (e.g. 0.1).
            // Recommended: 0.1 for medium traffic, 0.01 for high traffic.
            'traces_sample_rate' => env('SOLIDINVOICE_SENTRY_TRACES_SAMPLE_RATE')
                ->float(),
            // Profiling: requires the excimer PHP extension (see build-static.sh).
            // profiles_sample_rate is relative to traces_sample_rate: if traces=0.1 and profiles=1.0,
            // 10% of requests are traced and 100% of those traces are profiled.
            // Only set > 0 when excimer is installed; otherwise leave at 0.
            'profiles_sample_rate' => env('SOLIDINVOICE_SENTRY_PROFILES_SAMPLE_RATE')
                ->float(),
            // HTTP timeouts in seconds. Lower values (e.g. 1-2s) are safe when using a local Relay proxy,
            // which responds near-instantly and forwards asynchronously to sentry.io.
            // When sending directly to sentry.io, consider 5-10s to tolerate occasional latency.
            // To use Relay: change SOLIDINVOICE_SENTRY_DSN to point to your Relay instance,
            // e.g. http://<key>@localhost:3000/<project-id>, and keep timeouts at 2s.
            'http_timeout' => env('SOLIDINVOICE_SENTRY_HTTP_TIMEOUT')
                ->float(),
            'http_connect_timeout' => env('SOLIDINVOICE_SENTRY_HTTP_CONNECT_TIMEOUT')
                ->float(),
            // Ignore noisy internal/infrastructure transactions that add volume without insight.
            'ignore_transactions' => ['GET /_fragment'],
        ],
        // Symfony-specific tracing integrations. These register lightweight service decorators
        // unconditionally — even when traces_sample_rate=0. The overhead is a single null-check
        // per operation (no active span → immediate return), which is negligible in practice.
        // All integrations are pre-wired so that tracing can be enabled purely via the env var
        // without a redeploy or config change.
        'tracing' => [
            'enabled' => true,
            'dbal' => ['enabled' => true],        // Traces every Doctrine SQL query as a child span
            'twig' => ['enabled' => true],        // Traces Twig template renders
            'cache' => ['enabled' => true],       // Traces Symfony Cache hits and misses
            'http_client' => ['enabled' => true], // Traces outgoing Symfony HttpClient requests
            'console' => [
                // Long-running workers must be excluded: they would create a single trace that
                // spans the entire worker lifetime rather than per-message traces.
                'excluded_commands' => ['messenger:consume', 'schedule:run', 'cron:run'],
            ],
        ],
    ],
    'services' => [
        // The `sentry` handler type was removed in monolog-bundle 4,
        // the Sentry monolog handler needs to be registered as a service instead.
        Handler::class => [
            'arguments' => [
                '$hub' => '@' . HubInterface::class,
                '$level' => Level::Error->value,
            ],
        ],
    ],
    'monolog' => [
        'handlers' => [
            'sentry_main' => [
                'type' => 'service',
                'id' => Handler::class,
            ],
            'sentry' => [
                'type' => 'fingers_crossed',
                'action_level' => Level::Error->value,
                'handler' => 'sentry_main',
                'excluded_http_codes' => [401, 404, 405],
                'buffer_size' => 50,
            ],
            'sentry_logs' => [
                'type' => 'service',
                'id' => LogsHandler::class,
                'channels' => [
                    'elements' => ['!doctrine', '!request', '!security', '!event', '!console'],
                ],
            ],
        ],
    ],
]);
