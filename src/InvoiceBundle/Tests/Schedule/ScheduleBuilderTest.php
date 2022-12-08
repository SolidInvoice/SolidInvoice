<?php
declare(strict_types=1);

namespace SolidInvoice\InvoiceBundle\Tests\Schedule;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Message\CreateInvoiceFromRecurring;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\InvoiceBundle\Schedule\ScheduleBuilder;
use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Task\MessageTask;

/** @covers \SolidInvoice\InvoiceBundle\Schedule\ScheduleBuilder */
final class ScheduleBuilderTest extends TestCase
{
    public function testScheduleIsSkippedForInactiveRecurringInvoices(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $repository = $this->createMock(RecurringInvoiceRepository::class);

        $registry->expects(self::once())
            ->method('getRepository')
            ->with(RecurringInvoice::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findBy')
            ->with(['status' => 'active'])
            ->willReturn([]);

        $builder = new ScheduleBuilder($registry);

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        self::assertSame([], $schedule->all());
    }

    public function testSkipInvoicesAfterEndDate(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setDateEnd(new DateTimeImmutable('yesterday'));

        $registry = $this->createMock(ManagerRegistry::class);
        $repository = $this->createMock(RecurringInvoiceRepository::class);

        $registry->expects(self::once())
            ->method('getRepository')
            ->with(RecurringInvoice::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findBy')
            ->with(['status' => 'active'])
            ->willReturn([$recurringInvoice]);

        $builder = new ScheduleBuilder($registry);

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        self::assertSame([], $schedule->all());
    }

    public function testScheduleIsAddedForRecurringInvoices(): void
    {
        $recurringInvoice = new RecurringInvoice();
        $recurringInvoice->setFrequency('0 0 1 * *');

        $registry = $this->createMock(ManagerRegistry::class);
        $repository = $this->createMock(RecurringInvoiceRepository::class);

        $registry->expects(self::once())
            ->method('getRepository')
            ->with(RecurringInvoice::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findBy')
            ->with(['status' => 'active'])
            ->willReturn([$recurringInvoice]);

        $builder = new ScheduleBuilder($registry);

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        self::assertCount(1, $schedule->all());

        foreach ($schedule->all() as $job) {
            self::assertSame('0 0 1 * *', $job->getExpression()->getRawValue());
            self::assertSame('Create recurring invoice (0)', $job->getDescription());
            self::assertSame('MessageTask', $job->getType());
            self::assertInstanceOf(MessageTask::class, $job);
            self::assertInstanceOf(CreateInvoiceFromRecurring::class, $job->getMessage());
            self::assertSame($recurringInvoice, $job->getMessage()->getRecurringInvoice());
        }
    }

    public function testOneScheduleIsAddedForRecurringInvoices(): void
    {
        $recurringInvoice1 = new RecurringInvoice();
        $recurringInvoice1->setFrequency('0 0 1 * *');

        $recurringInvoice2 = new RecurringInvoice();
        $recurringInvoice2->setDateEnd(new DateTimeImmutable('yesterday'));

        $recurringInvoice3 = new RecurringInvoice();
        $recurringInvoice3->setFrequency('* * 1 1 1');

        $registry = $this->createMock(ManagerRegistry::class);
        $repository = $this->createMock(RecurringInvoiceRepository::class);

        $registry->expects(self::once())
            ->method('getRepository')
            ->with(RecurringInvoice::class)
            ->willReturn($repository);

        $repository->expects(self::once())
            ->method('findBy')
            ->with(['status' => 'active'])
            ->willReturn([$recurringInvoice1, $recurringInvoice2, $recurringInvoice3]);

        $builder = new ScheduleBuilder($registry);

        $schedule = new Schedule();
        $builder->buildSchedule($schedule);

        $tasks = $schedule->all();
        self::assertCount(2, $tasks);

        self::assertSame('0 0 1 * *', $tasks[0]->getExpression()->getRawValue());
        self::assertSame('Create recurring invoice (0)', $tasks[0]->getDescription());
        self::assertSame('MessageTask', $tasks[0]->getType());
        self::assertInstanceOf(MessageTask::class, $tasks[0]);
        self::assertInstanceOf(CreateInvoiceFromRecurring::class, $tasks[0]->getMessage());
        self::assertSame($recurringInvoice1, $tasks[0]->getMessage()->getRecurringInvoice());

        self::assertSame('* * 1 1 1', $tasks[1]->getExpression()->getRawValue());
        self::assertSame('Create recurring invoice (0)', $tasks[1]->getDescription());
        self::assertSame('MessageTask', $tasks[1]->getType());
        self::assertInstanceOf(MessageTask::class, $tasks[1]);
        self::assertInstanceOf(CreateInvoiceFromRecurring::class, $tasks[1]->getMessage());
        self::assertSame($recurringInvoice3, $tasks[1]->getMessage()->getRecurringInvoice());
    }
}
