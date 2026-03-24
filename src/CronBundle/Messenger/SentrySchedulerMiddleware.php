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
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;
use Throwable;
use function Sentry\captureCheckIn;
use function Sentry\captureException;
use function Sentry\startTransaction;
use function Sentry\withScope;

final class SentrySchedulerMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $scheduledStamp = $envelope->last(ScheduledStamp::class);

        if ($scheduledStamp === null || $envelope->last(ReceivedStamp::class) === null || SentrySdk::getCurrentHub()->getClient() === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $hub = SentrySdk::getCurrentHub();
        $slug = $this->getSlug($envelope->getMessage(), $scheduledStamp);
        $monitorConfig = $this->buildMonitorConfig($scheduledStamp);

        // withScope() creates an isolated scope layer for this job execution.
        // Any scope mutations (tags, user, breadcrumbs, span) made by the handler
        // or Sentry integrations are automatically discarded when the scope exits,
        // preventing context leakage between consecutive messages in a long-lived worker.
        return withScope(function () use ($hub, $envelope, $stack, $slug, $monitorConfig): Envelope {
            $transactionContext = new TransactionContext();
            $transactionContext->setName($slug);
            $transactionContext->setOp('queue.process');
            $transactionContext->setSource(TransactionSource::task());

            $transaction = startTransaction($transactionContext);

            // Set as the active span. Hub::setSpan() mutates the current (inner) scope
            // layer created by withScope(), so the previous span is restored automatically
            // when the scope exits — no manual save/restore needed.
            $hub->setSpan($transaction);

            // Only emit check-ins when we have a monitor config (i.e. a known schedule).
            // Without one, Sentry would create orphaned unmanaged check-ins with no
            // associated schedule — useless noise. Tracing still runs in all cases.
            $checkInId = $monitorConfig !== null ? captureCheckIn(
                slug: $slug,
                status: CheckInStatus::inProgress(),
                monitorConfig: $monitorConfig,
            ) : null;

            $startTime = microtime(true);

            try {
                $result = $stack->next()->handle($envelope, $stack);

                $transaction->setStatus(SpanStatus::ok());

                if ($monitorConfig !== null) {
                    captureCheckIn(
                        slug: $slug,
                        status: CheckInStatus::ok(),
                        duration: microtime(true) - $startTime,
                        checkInId: $checkInId,
                    );
                }

                return $result;
            } catch (Throwable $e) {
                $transaction->setStatus(SpanStatus::internalError());

                // Explicitly capture the exception so it appears in Sentry Issues.
                // The global error handler is not guaranteed to run in queue/scheduler
                // contexts, so without this the cron monitor turns red but no Issue is filed.
                captureException($e);

                if ($monitorConfig !== null) {
                    captureCheckIn(
                        slug: $slug,
                        status: CheckInStatus::error(),
                        duration: microtime(true) - $startTime,
                        checkInId: $checkInId,
                    );
                }

                throw $e;
            } finally {
                $transaction->finish();
            }
        });
    }

    private function getSlug(object $message, ScheduledStamp $stamp): string
    {
        if ($message instanceof RunCommandMessage) {
            $name = explode(' ', $message->input, 2)[0];
        } else {
            $shortName = strrchr($message::class, '\\');
            $name = $shortName !== false ? substr($shortName, 1) : $message::class;
        }

        // Combine schedule name and message name, normalise to [a-z0-9_-], cap at 50 chars.
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', strtolower($stamp->messageContext->name . '-' . $name));

        return substr($slug, 0, 50);
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
