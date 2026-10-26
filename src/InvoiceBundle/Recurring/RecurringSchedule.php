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

namespace SolidInvoice\InvoiceBundle\Recurring;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Carbon\Unit;
use Carbon\WeekDay;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Arr;
use NumberFormatter;
use Psr\Clock\ClockInterface;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_slice;
use function array_sum;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Recurring\RecurringScheduleTest
 */
readonly class RecurringSchedule
{
    public function __construct(
        private string $locale,
        private ClockInterface $clock,
        private TranslatorInterface $translator,
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

                ++$totalOccurrence;
            }

            if ($totalOccurrence === $limit && $options->getEndType()->isNever()) {
                break;
            }

            if (($totalOccurrence === $options->getEndOccurrence() || $totalOccurrence === $limit) && $options->getEndType()->isAfter()) {
                break;
            }

            if ($endDate instanceof CarbonInterface && $date->greaterThanOrEqualTo($endDate) && $options->getEndType()->isOn()) {
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
            if (! $options->getEndDate() instanceof DateTimeImmutable) {
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

        $type = $options->getType();
        $days = $options->getDays();

        if (! $type->isDaily() && [] === $days) {
            return '';
        }

        $formatter = new NumberFormatter($this->locale, NumberFormatter::ORDINAL);
        $join = fn (array $items): string => Arr::join(
            $items,
            $this->translator->trans('invoice.recurring.frequency_summary.list_separator'),
            $this->translator->trans('invoice.recurring.frequency_summary.list_last_separator'),
        );

        return match ($type) {
            ScheduleRecurringType::DAILY => $this->translator->trans('invoice.recurring.frequency_summary.daily'),
            ScheduleRecurringType::WEEKLY => $this->translator->trans('invoice.recurring.frequency_summary.weekly', [
                '%days%' => $join(array_map(fn ($day): string => $this->translator->trans('invoice.recurring.days.' . strtolower(($day instanceof WeekDay ? $day : WeekDay::from((int) $day))->name)), $days)),
            ]),
            ScheduleRecurringType::MONTHLY => $this->translator->trans('invoice.recurring.frequency_summary.monthly', [
                '%days%' => $join(array_map(static fn ($day): string => (string) $formatter->format((int) $day), $days)),
            ]),
            ScheduleRecurringType::YEARLY => $this->translator->trans('invoice.recurring.frequency_summary.yearly', [
                '%months%' => $join(array_map(fn ($month): string => $this->translator->trans('invoice.recurring.months.' . strtolower((string) CarbonImmutable::create(null, (int) $month)?->format('F'))), $days)),
            ]),
        };
    }
}
