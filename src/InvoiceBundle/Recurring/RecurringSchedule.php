<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Recurring;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Unit;
use Carbon\WeekDay;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Arr;
use NumberFormatter;
use Psr\Clock\ClockInterface;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use function array_slice;
use function array_sum;

readonly class RecurringSchedule
{
    public function __construct(
        private string $locale,
        private ClockInterface $clock
    ) {
    }

    /**
     * @return iterable<CarbonInterface>
     * @throws Exception
     */
    public function getNextOccurrences(RecurringOptions $options, int $limit = 10): iterable
    {
        yield from $this->getOccurrences($options, $this->clock->now(), $limit);
    }

    /**
     * @return iterable<CarbonInterface>
     * @throws Exception
     */
    public function getOccurrences(RecurringOptions $options, ?DateTimeInterface $startDate = null, int $limit = 10): iterable
    {
        $start = CarbonImmutable::instance($startDate ?? $options->getRecurringInvoice()->getDateStart());
        $totalOccurrence = 0;

        $dates = match ($options->getType()) {
            ScheduleRecurringType::DAILY => $start->range($start->addDays($options->getEndOccurrence() ?? $limit), Unit::Day->interval()),
            ScheduleRecurringType::WEEKLY => $start->range($start->addWeeks($options->getEndOccurrence() ?? $limit)),
            ScheduleRecurringType::MONTHLY => $start->range($start->addMonths($options->getEndOccurrence() ?? $limit)),
            ScheduleRecurringType::YEARLY => $start->range($start->addYears($options->getEndOccurrence() ?? $limit), Unit::Month->interval()),
        };

        $endDate = $this->getEndDate($options);

        foreach ($dates->getIterator() as $date) {
            if (match ($options->getType()) {
                ScheduleRecurringType::DAILY => true,
                ScheduleRecurringType::WEEKLY => in_array($date->dayOfWeek, $options->getDays(), true),
                ScheduleRecurringType::MONTHLY => in_array($date->day, $options->getDays(), true),
                ScheduleRecurringType::YEARLY => in_array($date->month, $options->getDays(), true),
            }) {
                yield $date;

                $totalOccurrence++;
            }

            if ($totalOccurrence === $limit && $options->getEndType()->isNever()) {
                break;
            }

            if (($totalOccurrence === $options->getEndOccurrence() || $totalOccurrence === $limit) && $options->getEndType()->isAfter()) {
                break;
            }

            if ($endDate && $date->greaterThanOrEqualTo($endDate) && $options->getEndType()->isOn()) {
                break;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function getNextRunDate(RecurringOptions $options): ?CarbonInterface
    {
        foreach ($this->getNextOccurrences($options) as $occurrence) {
            return $occurrence;
        }

        return null;
    }

    public function getEndDate(RecurringOptions $options): ?CarbonInterface
    {
        $scheduleEndType = $options->getEndType();

        if ($scheduleEndType->isNever()) {
            return null;
        }

        if ($scheduleEndType->isOn()) {
            if (null === $options->getEndDate()) {
                return null;
            }

            // @TODO: Calculate the last day that the schedule needs to run
            // E.G The schedule is set to run weekly on Wednesday, but the end date is set to a Sunday.
            // This should then return the last Wednesday that the schedule should run
            return CarbonImmutable::instance($options->getEndDate());
        }

        $start = CarbonImmutable::instance($options->getRecurringInvoice()->getDateStart());

        $scheduleType = $options->getType();
        $days = $options->getDays();
        $totalDays = count($days) ?: 1;
        $totalOccurrences = $options->getEndOccurrence();
        $scheduleOccurrences = (int) ceil(($totalOccurrences ?: 1) / $totalDays) - 1;

        $totalDaysElapsed = $scheduleOccurrences * $totalDays;
        $daysLeft = array_sum(array_slice($days, 0, $totalOccurrences - $totalDaysElapsed));

        return match ($scheduleType) {
            ScheduleRecurringType::DAILY => $start->addDays($totalOccurrences),
            ScheduleRecurringType::WEEKLY => $start->addWeeks($scheduleOccurrences)->startOf(Unit::Week)->addDays($daysLeft),
            ScheduleRecurringType::MONTHLY => $start->addMonths($scheduleOccurrences)->startOf(Unit::Month)->addDays($daysLeft > 0 ? $daysLeft - 1 : 0),
            ScheduleRecurringType::YEARLY => $start->addYears($scheduleOccurrences)->startOf(Unit::Year)->addMonths($daysLeft),
        };
    }

    public function getFrequency(RecurringOptions $options): string
    {
        if (! $options->hasType()) {
            return '';
        }

        $formatter = new NumberFormatter($this->locale, NumberFormatter::ORDINAL);

        return ($options->getType()->isDaily() || [] !== $options->getDays()) ? 'Every ' . match ($options->getType()) {
            ScheduleRecurringType::DAILY => 'day',
            ScheduleRecurringType::WEEKLY => Arr::join(array_map(static fn (WeekDay $day) => $day->name, array_map(WeekDay::from(...), $options->getDays())), ', ', ' and '),
            ScheduleRecurringType::MONTHLY => sprintf('%s of the month', Arr::join(array_map(static fn ($day) => $formatter->format((int) $day), $options->getDays()), ', ', ' and ')),
            ScheduleRecurringType::YEARLY => Arr::join(array_map(static fn ($month) => CarbonImmutable::create(null, $month)?->format('F'), $options->getDays()), ', ', ' and '),
        } : '';
    }
}
