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
use Sentry\MonitorScheduleUnit;
use Sentry\SentrySdk;
use Symfony\Component\Console\Messenger\RunCommandMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Scheduler\Messenger\ScheduledStamp;
use Symfony\Component\Scheduler\Trigger\CronExpressionTrigger;
use Throwable;
use function Sentry\captureCheckIn;
use function str_replace;

final class SentrySchedulerMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $scheduledStamp = $envelope->last(ScheduledStamp::class);

        if ($scheduledStamp === null || SentrySdk::getCurrentHub()->getClient() === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $slug = $this->getSlug($envelope->getMessage());
        $monitorConfig = $this->buildMonitorConfig($scheduledStamp);

        $checkInId = captureCheckIn(
            slug: $slug,
            status: CheckInStatus::inProgress(),
            monitorConfig: $monitorConfig,
        );

        try {
            $result = $stack->next()->handle($envelope, $stack);

            captureCheckIn(
                slug: $slug,
                status: CheckInStatus::ok(),
                checkInId: $checkInId,
            );

            return $result;
        } catch (Throwable $e) {
            captureCheckIn(
                slug: $slug,
                status: CheckInStatus::error(),
                checkInId: $checkInId,
            );

            throw $e;
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

    private function buildMonitorConfig(ScheduledStamp $stamp): MonitorConfig
    {
        $trigger = $stamp->messageContext->trigger;

        if ($trigger instanceof CronExpressionTrigger) {
            return new MonitorConfig(MonitorSchedule::crontab((string) $trigger));
        }

        return new MonitorConfig(MonitorSchedule::interval(60, MonitorScheduleUnit::minute()));
    }
}
