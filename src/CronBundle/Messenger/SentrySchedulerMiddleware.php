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

namespace SolidInvoice\CronBundle\Messenger;

use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanStatus;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;
use Throwable;
use function Sentry\captureCheckIn;
use function Sentry\startTransaction;
use function str_replace;

final class SentrySchedulerMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $scheduledStamp = $envelope->last(ScheduledStamp::class);

        if ($scheduledStamp === null || SentrySdk::getCurrentHub()->getClient() === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $hub = SentrySdk::getCurrentHub();
        $slug = $this->getSlug($envelope->getMessage());
        $monitorConfig = $this->buildMonitorConfig($scheduledStamp);

        // Start a performance transaction so Doctrine/Cache/HttpClient integrations
        // can attach child spans and the job appears in Sentry Performance.
        // startTransaction() respects traces_sample_rate: if it is 0 the transaction
        // is created but never sent, so there is no meaningful overhead.
        $transactionContext = new TransactionContext();
        $transactionContext->setName($slug);
        $transactionContext->setOp('queue.process');
        $transactionContext->setSource(TransactionSource::task());

        $transaction = startTransaction($transactionContext);

        // Make this transaction the active span so all integrations (DBAL, Cache, HTTP
        // client) automatically attach their child spans to it.
        $previousSpan = $hub->getSpan();
        $hub->setSpan($transaction);

        $checkInId = captureCheckIn(
            slug: $slug,
            status: CheckInStatus::inProgress(),
            monitorConfig: $monitorConfig,
        );

        try {
            $result = $stack->next()->handle($envelope, $stack);

            $transaction->setStatus(SpanStatus::ok());

            captureCheckIn(
                slug: $slug,
                status: CheckInStatus::ok(),
                checkInId: $checkInId,
            );

            return $result;
        } catch (Throwable $e) {
            $transaction->setStatus(SpanStatus::internalError());

            captureCheckIn(
                slug: $slug,
                status: CheckInStatus::error(),
                checkInId: $checkInId,
            );

            throw $e;
        } finally {
            $transaction->finish();
            $hub->setSpan($previousSpan);
        }
    }

    private function getSlug(object $message): string
    {
        if ($message instanceof RunCommandMessage) {
            $parts = explode(' ', $message->input, 2);

            return str_replace(':', '-', $parts[0]);
        }

        $class = (new \ReflectionClass($message))->getShortName();

        return strtolower((string) preg_replace('/[A-Z]/', '-$0', lcfirst($class)));
    }

    private function buildMonitorConfig(ScheduledStamp $stamp): ?MonitorConfig
    {
        $trigger = $stamp->messageContext->trigger;

        if ($trigger instanceof CronExpressionTrigger) {
            return new MonitorConfig(MonitorSchedule::crontab((string) $trigger));
        }

        return null;
    }
}
