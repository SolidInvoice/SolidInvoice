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
use DateTimeImmutable;
use SolidInvoice\CoreBundle\Entity\BillingPeriod;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\BillingPeriodMode;

/**
 * Derives the invoicing period (EN 16931 BG-14) a generated recurring invoice states, from the
 * recurring template's own schedule unit: a monthly schedule bills a month, a yearly schedule
 * bills a year.
 *
 * Pure: no clock, no repository, no entity manager. The caller supplies the generation date.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Recurring\BillingPeriodCalculatorTest
 */
final readonly class BillingPeriodCalculator
{
    public function calculate(RecurringInvoice $recurringInvoice, DateTimeImmutable $generatedOn): BillingPeriod
    {
        $mode = $recurringInvoice->getBillingPeriodMode();

        if ($mode === BillingPeriodMode::None) {
            return new BillingPeriod();
        }

        $recurringOptions = $recurringInvoice->getRecurringOptions();

        // A half-built template can have no schedule type yet. No type means no period,
        // the same guard RecurringSchedule::getFrequency() uses.
        if (! $recurringOptions->hasType()) {
            return new BillingPeriod();
        }

        $type = $recurringOptions->getType();
        $now = CarbonImmutable::instance($generatedOn)->startOfDay();
        $reference = $mode === BillingPeriodMode::Previous ? $this->previous($now, $type) : $now;

        [$start, $end] = $this->bounds($reference, $type);

        return BillingPeriod::of($start, $end);
    }

    private function previous(CarbonImmutable $now, ScheduleRecurringType $type): CarbonImmutable
    {
        return match ($type) {
            ScheduleRecurringType::DAILY => $now->subDay(),
            ScheduleRecurringType::WEEKLY => $now->subWeek(),
            // Never subMonth()/subYear() on the day itself: the 31st or a leap day would
            // overflow into the wrong month. PaymentTerms::dueDate() hits the same trap.
            ScheduleRecurringType::MONTHLY => $now->subMonthNoOverflow(),
            ScheduleRecurringType::YEARLY => $now->subYearNoOverflow(),
        };
    }

    /**
     * @return array{0: DateTimeImmutable, 1: DateTimeImmutable}
     */
    private function bounds(CarbonImmutable $reference, ScheduleRecurringType $type): array
    {
        // The columns are DATE_IMMUTABLE: every boundary is normalised to startOfDay(), even
        // the end of an interval, which Carbon otherwise leaves at 23:59:59.999999.
        return match ($type) {
            ScheduleRecurringType::DAILY => [$reference->startOfDay(), $reference->startOfDay()],
            ScheduleRecurringType::WEEKLY => [$reference->startOfWeek()->startOfDay(), $reference->endOfWeek()->startOfDay()],
            ScheduleRecurringType::MONTHLY => [$reference->startOfMonth()->startOfDay(), $reference->endOfMonth()->startOfDay()],
            ScheduleRecurringType::YEARLY => [$reference->startOfYear()->startOfDay(), $reference->endOfYear()->startOfDay()],
        };
    }
}
