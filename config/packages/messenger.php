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

use SolidInvoice\CoreBundle\Export\Message\RequestCompanyExport;
use SolidInvoice\CoreBundle\Telemetry\Message\SendTelemetryMessage;
use SolidInvoice\CronBundle\Messenger\SentrySchedulerMiddleware;
use SolidInvoice\InvoiceBundle\Message\SendInvoiceReminderMessage;
use SolidInvoice\SaasBundle\Message\SendOnboardingEmailMessage;
use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $config): void {
    $messenger = $config->messenger();

    // Failure transport - always uses Doctrine for reliability
    $messenger->transport('failed')
        ->dsn('doctrine://default?queue_name=failed');

    // Main async transport - uses env variable with fallback to Doctrine (set in services.php)
    // This single transport handles all async messages
    // Scale by running multiple workers: bin/console messenger:consume async --limit=100
    $messenger->transport('async')
        ->dsn(env('SOLIDINVOICE_MESSENGER_DSN'))
        ->retryStrategy()
        ->maxRetries(3)
        ->delay(1000) // 1 second initial delay
        ->multiplier(2) // Exponential backoff
        ->maxDelay(60000) // Max 60 seconds between retries
        ->jitter(0.1); // 10% random jitter to prevent thundering herd

    // Route onboarding emails through the async transport so the hourly
    // scheduler returns quickly and Messenger's retry strategy handles
    // transient mailer failures.
    $messenger->routing(SendOnboardingEmailMessage::class)
        ->senders(['async']);

    // Route invoice reminder dispatch through the async transport for the same
    // reason: the hourly cron command must return quickly (seconds), not block
    // while sending one SMTP email per qualifying invoice.  Without this routing
    // the handler runs synchronously and a slow/unreachable mail server causes
    // the cron to exceed Sentry's max_runtime → perpetual "timeout" alerts.
    $messenger->routing(SendInvoiceReminderMessage::class)
        ->senders(['async']);

    // Full company data exports are long-running and email the user on completion,
    // so they must run out-of-band from the HTTP request that triggered them.
    $messenger->routing(RequestCompanyExport::class)
        ->senders(['async']);

    // Telemetry signals must be fire-and-forget — routing them through the async
    // transport keeps the triggering web request fast and lets the worker drain
    // them out-of-band. The handler swallows all errors and always acks, so a
    // slow or unreachable Insights server never blocks the app or retries.
    $messenger->routing(SendTelemetryMessage::class)
        ->senders(['async']);

    // Configure default bus
    $messenger->defaultBus('messenger.bus.default');

    $messenger->bus('messenger.bus.default')
        ->middleware()
        ->id(SentrySchedulerMiddleware::class);

    // Configure failure transport
    $messenger->failureTransport('failed');
};
