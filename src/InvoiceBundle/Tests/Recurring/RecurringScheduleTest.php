<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Tests\Recurring;

use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use SolidInvoice\CronBundle\Enum\ScheduleEndType;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringOptions;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;
use Symfony\Component\Clock\MockClock;
use function iterator_to_array;

final class RecurringScheduleTest extends TestCase
{
    /**
     * @dataProvider getEndDateDataProvider
     */
    public function testGetEndDate(RecurringOptions $options, ?string $expected): void
    {
        $scheduleFormatter = new RecurringSchedule('en', $this->createMock(ClockInterface::class));

        self::assertEquals($expected, $scheduleFormatter->getEndDate($options)?->format('Y-m-d'));
    }

    /**
     * @param list<string> $expected
     *
     * @dataProvider getOccurrencesProvider
     */
    public function testGetOccurrences(RecurringOptions $options, array $expected): void
    {
        $scheduleFormatter = new RecurringSchedule('en', new MockClock('2024-01-01'));

        $occurrences = array_map(static fn (DateTimeInterface $date) => $date->format('Y-m-d'), iterator_to_array($scheduleFormatter->getOccurrences($options)));

        self::assertSame($expected, $occurrences);
    }

    public function testGetNextRunDate(): void
    {
        $this->markTestSkipped('Not implemented');
    }

    /**
     * @return iterable<array{options: RecurringOptions, expected: string|null}>
     */
    public static function getEndDateDataProvider(): iterable
    {
        // DAILY
        yield 'daily schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::DAILY);
                $recurringOptions->setEndOccurrence(10);

                return $recurringOptions;
            })(),
            'expected' => '2024-01-11',
        ];
        yield 'daily schedule ending on specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-01-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::DAILY);

                return $recurringOptions;
            })(),
            'expected' => '2024-01-15',
        ];
        yield 'never ending daily schedule' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::NEVER);
                $recurringOptions->setType(ScheduleRecurringType::DAILY);

                return $recurringOptions;
            })(),
            'expected' => null,
        ];

        // WEEKLY
        yield 'weekly schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);
                $recurringOptions->setEndOccurrence(10);

                return $recurringOptions;
            })(),
            'expected' => '2024-03-04',
        ];
        yield 'weekly schedule ending after 5 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);
                $recurringOptions->setEndOccurrence(5);

                return $recurringOptions;
            })(),
            'expected' => '2024-01-29',
        ];
        yield 'weekly schedule running on specific days after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([1, 3, 5]);

                return $recurringOptions;
            })(),
            'expected' => '2024-01-23',
        ];
        yield 'weekly schedule running on specific days, ending on a specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-01-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);

                return $recurringOptions;
            })(),
            'expected' => '2024-01-15',
        ];

        // MONTHLY
        yield 'monthly schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);
                $recurringOptions->setEndOccurrence(10);

                return $recurringOptions;
            })(),
            'expected' => '2024-10-01',
        ];
        yield 'monthly schedule ending after 5 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);
                $recurringOptions->setEndOccurrence(5);

                return $recurringOptions;
            })(),
            'expected' => '2024-05-01',
        ];
        yield 'monthly schedule running on specific days after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([5, 15, 25]);

                return $recurringOptions;
            })(),
            'expected' => '2024-04-05',
        ];
        yield 'monthly schedule running on specific days, ending on a specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-01-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);

                return $recurringOptions;
            })(),
            'expected' => '2024-01-15',
        ];

        // YEARLY
        yield 'yearly schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);
                $recurringOptions->setEndOccurrence(10);

                return $recurringOptions;
            })(),
            'expected' => '2033-01-01',
        ];
        yield 'yearly schedule ending after 5 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);
                $recurringOptions->setEndOccurrence(5);

                return $recurringOptions;
            })(),
            'expected' => '2028-01-01',
        ];
        yield 'yearly schedule running on specific days after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([3, 6, 9]);

                return $recurringOptions;
            })(),
            'expected' => '2027-04-01',
        ];
        yield 'yearly schedule ending on a specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-01-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);

                return $recurringOptions;
            })(),
            'expected' => '2024-01-15',
        ];
    }

    /**
     * @return iterable<array{options: RecurringOptions, expected: list<string>}>
     */
    public static function getOccurrencesProvider(): iterable
    {
        // DAILY
        yield 'daily schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::DAILY);
                $recurringOptions->setEndOccurrence(10);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-01',
                '2024-01-02',
                '2024-01-03',
                '2024-01-04',
                '2024-01-05',
                '2024-01-06',
                '2024-01-07',
                '2024-01-08',
                '2024-01-09',
                '2024-01-10',
            ],
        ];
        yield 'daily schedule ending on specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-01-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::DAILY);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-01',
                '2024-01-02',
                '2024-01-03',
                '2024-01-04',
                '2024-01-05',
                '2024-01-06',
                '2024-01-07',
                '2024-01-08',
                '2024-01-09',
                '2024-01-10',
                '2024-01-11',
            ],
        ];
        yield 'never ending daily schedule' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::NEVER);
                $recurringOptions->setType(ScheduleRecurringType::DAILY);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-01',
                '2024-01-02',
                '2024-01-03',
                '2024-01-04',
                '2024-01-05',
                '2024-01-06',
                '2024-01-07',
                '2024-01-08',
                '2024-01-09',
                '2024-01-10',
            ],
        ];

        // WEEKLY
        yield 'weekly schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);
                $recurringOptions->setDays([1]);
                $recurringOptions->setEndOccurrence(10);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-01',
                '2024-01-08',
                '2024-01-15',
                '2024-01-22',
                '2024-01-29',
                '2024-02-05',
                '2024-02-12',
                '2024-02-19',
                '2024-02-26',
                '2024-03-04',
            ],
        ];
        yield 'weekly schedule ending after 5 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);
                $recurringOptions->setDays([4]);
                $recurringOptions->setEndOccurrence(5);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-04',
                '2024-01-11',
                '2024-01-18',
                '2024-01-25',
                '2024-02-01',
            ],
        ];
        yield 'weekly schedule running on specific days after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([1, 3, 5]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-01',
                '2024-01-03',
                '2024-01-05',
                '2024-01-08',
                '2024-01-10',
                '2024-01-12',
                '2024-01-15',
                '2024-01-17',
                '2024-01-19',
                '2024-01-22',
            ],
        ];
        yield 'weekly schedule running on specific days, ending on a specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-01-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::WEEKLY);
                $recurringOptions->setDays([1, 3, 5]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-01',
                '2024-01-03',
                '2024-01-05',
                '2024-01-08',
                '2024-01-10',
                '2024-01-12',
                '2024-01-15',
            ],
        ];

        // MONTHLY
        yield 'monthly schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([1, 3, 5]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-01',
                '2024-01-03',
                '2024-01-05',
                '2024-02-01',
                '2024-02-03',
                '2024-02-05',
                '2024-03-01',
                '2024-03-03',
                '2024-03-05',
                '2024-04-01',
            ],
        ];
        yield 'monthly schedule ending after 5 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);
                $recurringOptions->setEndOccurrence(5);
                $recurringOptions->setDays([12, 22]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-12',
                '2024-01-22',
                '2024-02-12',
                '2024-02-22',
                '2024-03-12',
            ],
        ];
        yield 'monthly schedule running on specific days after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([5, 15, 25]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-05',
                '2024-01-15',
                '2024-01-25',
                '2024-02-05',
                '2024-02-15',
                '2024-02-25',
                '2024-03-05',
                '2024-03-15',
                '2024-03-25',
                '2024-04-05',
            ],
        ];
        yield 'monthly schedule running on specific days, ending on a specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-01-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::MONTHLY);
                $recurringOptions->setDays([5, 15, 25]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-01-05',
                '2024-01-15',
            ],
        ];

        // YEARLY
        yield 'yearly schedule ending after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([3, 10]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-03-01',
                '2024-10-01',
                '2025-03-01',
                '2025-10-01',
                '2026-03-01',
                '2026-10-01',
                '2027-03-01',
                '2027-10-01',
                '2028-03-01',
                '2028-10-01',
            ],
        ];
        yield 'yearly schedule ending after 5 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);
                $recurringOptions->setEndOccurrence(5);
                $recurringOptions->setDays([3, 10]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-03-01',
                '2024-10-01',
                '2025-03-01',
                '2025-10-01',
                '2026-03-01',
            ],
        ];
        yield 'yearly schedule running on specific days after 10 occurrences' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::AFTER);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);
                $recurringOptions->setEndOccurrence(10);
                $recurringOptions->setDays([3, 6, 9]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-03-01',
                '2024-06-01',
                '2024-09-01',
                '2025-03-01',
                '2025-06-01',
                '2025-09-01',
                '2026-03-01',
                '2026-06-01',
                '2026-09-01',
                '2027-03-01',
            ],
        ];
        yield 'yearly schedule ending on a specific date' => [
            'options' => (static function () {
                $recurringInvoice = new RecurringInvoice();
                $recurringInvoice->setDateStart(new \DateTimeImmutable('2024-01-01'));
                $recurringInvoice->setDateEnd(new \DateTimeImmutable('2024-05-15'));
                $recurringOptions = new RecurringOptions();
                $recurringOptions->setRecurringInvoice($recurringInvoice);

                $recurringOptions->setEndType(ScheduleEndType::ON);
                $recurringOptions->setType(ScheduleRecurringType::YEARLY);
                $recurringOptions->setDays([4]);

                return $recurringOptions;
            })(),
            'expected' => [
                '2024-04-01',
            ],
        ];
    }
}
