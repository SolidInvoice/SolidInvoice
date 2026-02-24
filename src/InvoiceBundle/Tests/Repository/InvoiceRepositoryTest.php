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

namespace SolidInvoice\InvoiceBundle\Tests\Repository;

use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceReminderFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\InvoiceBundle\Repository\InvoiceRepository */
final class InvoiceRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private InvoiceRepository $repository;

    private ClockInterface $clock;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');

        // Create a frozen clock for consistent test execution with UTC timezone
        $this->clock = new MockClock(new DateTimeImmutable('2024-02-01 10:00:00', new \DateTimeZone('UTC')));

        // Create repository with the frozen clock
        $this->repository = new InvoiceRepository($registry, $this->clock);
    }

    public function testGetInvoicesNeedingPreDueRemindersReturnsInvoicesDueInSpecifiedDays(): void
    {
        $dueDate = $this->clock->now()->modify('+3 days')->setTime(0, 0)->modify('+6 hours');

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $dueDate,
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(1, $results);
        self::assertSame($invoice->getId()->toBase32(), $results[0]->getId()->toBase32());
    }

    public function testGetInvoicesNeedingPreDueRemindersExcludesInvoicesAlreadySentReminder(): void
    {
        $dueDate = $this->clock->now()->modify('+3 days');

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $dueDate,
        ]);

        // Create a pre-due reminder for this invoice
        InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $this->company,
            'reminderType' => ReminderType::PreDue,
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(0, $results);
    }

    public function testGetInvoicesNeedingPreDueRemindersExcludesInvoicesNotDueInRange(): void
    {

        // Invoice due in 5 days (outside the 3-day window)
        InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $this->clock->now()->modify('+5 days'),
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(0, $results);
    }

    public function testGetInvoicesNeedingPreDueRemindersExcludesNonPendingInvoices(): void
    {
        $dueDate = $this->clock->now()->modify('+3 days');

        // Paid invoice
        InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Paid,
            'due' => $dueDate,
        ]);

        // Draft invoice
        InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Draft,
            'due' => $dueDate,
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(0, $results);
    }

    public function testGetInvoicesNeedingOverdueRemindersReturnsOverdueInvoices(): void
    {
        $dueDate = $this->clock->now()->modify('-1 day')->setTime(0, 0)->modify('+6 hours');

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $dueDate,
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingOverdueReminders(1, ReminderType::Overdue1));

        self::assertCount(1, $results);
        self::assertSame($invoice->getId()->toBase32(), $results[0]->getId()->toBase32());
    }

    public function testGetInvoicesNeedingOverdueRemindersExcludesInvoicesAlreadySentReminder(): void
    {
        $dueDate = $this->clock->now()->modify('-1 day');

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $dueDate,
        ]);

        // Create a 1-day overdue reminder
        InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $this->company,
            'reminderType' => ReminderType::Overdue1,
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingOverdueReminders(1, ReminderType::Overdue1));

        self::assertCount(0, $results);
    }

    public function testGetInvoicesNeedingOverdueRemindersReturnsInvoicesForSpecificOverdueDays(): void
    {

        // 1 day overdue
        InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $this->clock->now()->modify('-1 day')->setTime(0, 0)->modify('+6 hours'),
        ]);

        // 7 days overdue
        $invoice7Days = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $this->clock->now()->modify('-7 days')->setTime(0, 0)->modify('+6 hours'),
        ]);

        // 14 days overdue
        InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $this->clock->now()->modify('-14 days')->setTime(0, 0)->modify('+6 hours'),
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingOverdueReminders(7, ReminderType::Overdue7));

        self::assertCount(1, $results);
        self::assertSame($invoice7Days->getId()->toBase32(), $results[0]->getId()->toBase32());
    }

    public function testGetInvoicesNeedingOverdueRemindersExcludesPaidInvoices(): void
    {
        $dueDate = $this->clock->now()->modify('-1 day');

        // Paid invoice - should be excluded
        InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Paid,
            'due' => $dueDate,
        ]);

        // Overdue invoice - should be included now
        $overdueInvoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Overdue,
            'due' => $dueDate,
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingOverdueReminders(1, ReminderType::Overdue1));

        self::assertCount(1, $results);
        self::assertSame($overdueInvoice->getId()->toBase32(), $results[0]->getId()->toBase32());
    }
}
