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

namespace SolidInvoice\InvoiceBundle\Tests\Recurring;

use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\BillingPeriodMode;
use SolidInvoice\InvoiceBundle\Recurring\BillingPeriodCalculator;

final class BillingPeriodCalculatorTest extends TestCase
{
    /**
     * @param non-empty-string $generatedOn
     */
    #[DataProvider('tableDataProvider')]
    #[DataProvider('workedExampleDataProvider')]
    public function testCalculate(ScheduleRecurringType $type, BillingPeriodMode $mode, string $generatedOn, ?string $expectedStart, ?string $expectedEnd): void
    {
        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setBillingPeriodMode($mode);
        $recurringInvoice->getRecurringOptions()->setType($type);

        $period = new BillingPeriodCalculator()->calculate($recurringInvoice, CarbonImmutable::parse($generatedOn));

        self::assertSame($expectedStart, $period->getStart()?->format('Y-m-d'));
        self::assertSame($expectedEnd, $period->getEnd()?->format('Y-m-d'));
    }

    public function testNoneModeIsEmptyRegardlessOfSchedule(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setBillingPeriodMode(BillingPeriodMode::None);
        $recurringInvoice->getRecurringOptions()->setType(ScheduleRecurringType::MONTHLY);

        $period = new BillingPeriodCalculator()->calculate($recurringInvoice, CarbonImmutable::parse('2026-03-01'));

        self::assertTrue($period->isEmpty());
    }

    public function testUnsetScheduleTypeIsEmptyRatherThanAnException(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setBillingPeriodMode(BillingPeriodMode::Previous);

        $period = new BillingPeriodCalculator()->calculate($recurringInvoice, CarbonImmutable::parse('2026-03-01'));

        self::assertTrue($period->isEmpty());
    }

    /**
     * @return iterable<string, array{ScheduleRecurringType, BillingPeriodMode, string, ?string, ?string}>
     */
    public static function tableDataProvider(): iterable
    {
        yield 'daily, current' => [ScheduleRecurringType::DAILY, BillingPeriodMode::Current, '2026-03-11', '2026-03-11', '2026-03-11'];
        yield 'daily, previous' => [ScheduleRecurringType::DAILY, BillingPeriodMode::Previous, '2026-03-11', '2026-03-10', '2026-03-10'];
        yield 'weekly, current' => [ScheduleRecurringType::WEEKLY, BillingPeriodMode::Current, '2026-03-11', '2026-03-09', '2026-03-15'];
        yield 'weekly, previous' => [ScheduleRecurringType::WEEKLY, BillingPeriodMode::Previous, '2026-03-11', '2026-03-02', '2026-03-08'];
        yield 'monthly, current' => [ScheduleRecurringType::MONTHLY, BillingPeriodMode::Current, '2026-03-11', '2026-03-01', '2026-03-31'];
        yield 'monthly, previous' => [ScheduleRecurringType::MONTHLY, BillingPeriodMode::Previous, '2026-03-11', '2026-02-01', '2026-02-28'];
        yield 'yearly, current' => [ScheduleRecurringType::YEARLY, BillingPeriodMode::Current, '2026-03-11', '2026-01-01', '2026-12-31'];
        yield 'yearly, previous' => [ScheduleRecurringType::YEARLY, BillingPeriodMode::Previous, '2026-03-11', '2025-01-01', '2025-12-31'];
    }

    /**
     * Design §4.1's six worked examples, verbatim.
     *
     * @return iterable<string, array{ScheduleRecurringType, BillingPeriodMode, string, ?string, ?string}>
     */
    public static function workedExampleDataProvider(): iterable
    {
        yield 'monthly/previous generated on the 1st' => [ScheduleRecurringType::MONTHLY, BillingPeriodMode::Previous, '2026-03-01', '2026-02-01', '2026-02-28'];
        yield 'monthly/previous generated mid-month gives the same calendar interval' => [ScheduleRecurringType::MONTHLY, BillingPeriodMode::Previous, '2026-03-15', '2026-02-01', '2026-02-28'];
        yield 'monthly/previous across a leap February' => [ScheduleRecurringType::MONTHLY, BillingPeriodMode::Previous, '2024-03-01', '2024-02-01', '2024-02-29'];
        yield 'monthly/current generated on the 31st' => [ScheduleRecurringType::MONTHLY, BillingPeriodMode::Current, '2026-01-31', '2026-01-01', '2026-01-31'];
        yield 'yearly/previous' => [ScheduleRecurringType::YEARLY, BillingPeriodMode::Previous, '2026-01-01', '2025-01-01', '2025-12-31'];
    }
}
