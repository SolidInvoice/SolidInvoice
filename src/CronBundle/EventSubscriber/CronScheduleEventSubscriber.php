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

namespace SolidInvoice\CronBundle\EventSubscriber;

use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\MonitorSchedule;
use Sentry\MonitorScheduleUnit;
use Sentry\State\Scope;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use function date_default_timezone_get;
use function Sentry\captureCheckIn;
use function Sentry\configureScope;

final class CronScheduleEventSubscriber implements EventSubscriberInterface
{
    private const MONITOR_SLUG = 'cron-schedule';

    private ?string $checkInId = null;

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeScheduleEvent::class => 'beforeSchedule',
            AfterScheduleEvent::class => 'afterSchedule',
        ];
    }

    public function beforeSchedule(BeforeScheduleEvent $event): void
    {
        configureScope(function (Scope $scope): void {
            $scope->setContext('monitor', [
                'slug' => self::MONITOR_SLUG,
            ]);
        });

        $monitorSchedule = MonitorSchedule::interval(1, MonitorScheduleUnit::minute());

        $monitorConfig = new MonitorConfig(
            $monitorSchedule,
            2, // check-in margin in minutes
            5, // max runtime in minutes
        );

        $this->checkInId = captureCheckIn(
            self::MONITOR_SLUG,
            CheckInStatus::inProgress(),
            null,
            $monitorConfig,
            date_default_timezone_get()
        );
    }

    public function afterSchedule(AfterScheduleEvent $event): void
    {
        if ($this->checkInId === null || $this->checkInId === '') {
            return;
        }

        captureCheckIn(
            self::MONITOR_SLUG,
            CheckInStatus::ok(),
            $event->runContext()->getDuration(),
            null,
            $this->checkInId
        );

        $this->checkInId = null;
    }
}
