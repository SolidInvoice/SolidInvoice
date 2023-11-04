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

namespace SolidInvoice\InvoiceBundle\Tests\Schedule;

use DateTimeImmutable;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Schedule\ScheduleBuilder;
use SolidInvoice\InvoiceBundle\Test\Factory\RecurringInvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Task\MessageTask;

/** @covers \SolidInvoice\InvoiceBundle\Schedule\ScheduleBuilder */
final class ScheduleBuilderTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use Factories;

    public function testScheduleIsSkippedForInactiveRecurringInvoices(): void
    {
        $builder = new ScheduleBuilder($this->registry);

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        self::assertSame([], $schedule->all());
    }

    public function testSkipInvoicesAfterEndDate(): void
    {
        RecurringInvoiceFactory::new(['dateEnd' => new DateTimeImmutable('yesterday')]);

        $builder = new ScheduleBuilder($this->registry);

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        self::assertSame([], $schedule->all());
    }

    public function testScheduleIsAddedForRecurringInvoices(): void
    {
        /** @var RecurringInvoice $recurringInvoice */
        $recurringInvoice = RecurringInvoiceFactory::createOne([
            'frequency' => '0 0 1 * *',
            'status' => 'active',
            'dateStart' => new DateTimeImmutable('yesterday'),
            'dateEnd' => null,
        ])->object();

        $builder = new ScheduleBuilder(self::getContainer()->get('doctrine'));

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        self::assertCount(1, $schedule->all());

        foreach ($schedule->all() as $job) {
            self::assertSame('0 0 1 * *', $job->getExpression()->getRawValue());
            self::assertSame('Create recurring invoice ('.$recurringInvoice->getId()->toString().')', $job->getDescription());
            self::assertSame('MessageTask', $job->getType());
            self::assertInstanceOf(MessageTask::class, $job);
            self::assertInstanceOf(CreateInvoiceFromRecurring::class, $job->getMessage());
            self::assertSame($recurringInvoice, $job->getMessage()->getRecurringInvoice());
        }
    }

    public function testOneScheduleIsAddedForRecurringInvoices(): void
    {
        /** @var RecurringInvoice $recurringInvoice1 */
        $recurringInvoice1 = RecurringInvoiceFactory::createOne([
            'status' => 'active',
            'frequency' => '0 0 1 * *',
            'dateEnd' => null,
        ])->object();

        RecurringInvoiceFactory::createOne([
            'dateEnd' => new DateTimeImmutable('yesterday'),
        ]);

        /** @var RecurringInvoice $recurringInvoice3 */
        $recurringInvoice3 = RecurringInvoiceFactory::createOne([
            'status' => 'active',
            'frequency' => '* * 1 1 1',
            'dateEnd' => null,
        ])->object();

        $builder = new ScheduleBuilder(self::getContainer()->get('doctrine'));

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        $tasks = $schedule->all();
        self::assertCount(2, $tasks);

        self::assertSame('0 0 1 * *', $tasks[0]->getExpression()->getRawValue());
        self::assertSame('Create recurring invoice ('.$recurringInvoice1->getId()->toString().')', $tasks[0]->getDescription());
        self::assertSame('MessageTask', $tasks[0]->getType());
        self::assertInstanceOf(MessageTask::class, $tasks[0]);
        self::assertInstanceOf(CreateInvoiceFromRecurring::class, $tasks[0]->getMessage());
        self::assertSame($recurringInvoice1, $tasks[0]->getMessage()->getRecurringInvoice());

        self::assertSame('* * 1 1 1', $tasks[1]->getExpression()->getRawValue());
        self::assertSame('Create recurring invoice ('.$recurringInvoice3->getId()->toString().')', $tasks[1]->getDescription());
        self::assertSame('MessageTask', $tasks[1]->getType());
        self::assertInstanceOf(MessageTask::class, $tasks[1]);
        self::assertInstanceOf(CreateInvoiceFromRecurring::class, $tasks[1]->getMessage());
        self::assertSame($recurringInvoice3, $tasks[1]->getMessage()->getRecurringInvoice());
    }
}
