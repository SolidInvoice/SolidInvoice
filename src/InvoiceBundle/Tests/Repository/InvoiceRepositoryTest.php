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
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Clock\ClockInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceReminderFactory;
use SolidInvoice\SettingsBundle\Entity\Setting;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Ulid;
use Zenstruck\Foundry\Test\Factories;

#[CoversClass(InvoiceRepository::class)]
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
        $this->clock = new MockClock(new DateTimeImmutable('2024-02-01 10:00:00', new DateTimeZone('UTC')));

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
        self::assertSame($invoice->getId()->toBase32(), $results[0]['invoiceId']->toBase32());
    }

    /**
     * The scan hydrates scalars for speed, which hands back raw driver values — a binary string for
     * a ULID, a plain string for the date. They have to come back out as the mapped types, or the
     * dispatched message carries a binary id and never works out the days until due.
     */
    public function testGetInvoicesNeedingPreDueRemindersReturnsMappedTypesNotRawDriverValues(): void
    {
        InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $this->clock->now()->modify('+3 days'),
        ]);

        $results = iterator_to_array($this->repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(1, $results);
        self::assertInstanceOf(Ulid::class, $results[0]['invoiceId']);
        self::assertInstanceOf(Ulid::class, $results[0]['companyId']);
        self::assertInstanceOf(DateTimeImmutable::class, $results[0]['due']);
        self::assertSame($this->company->getId()->toBase32(), $results[0]['companyId']->toBase32());
        self::assertSame('2024-02-04', $results[0]['due']->format('Y-m-d'));
    }

    /**
     * The setting column is free text, so the same window can be stored as "3", "03" or "3 days".
     * getConfiguredPreDueDays() normalises them all to 3 and the scan runs once for that window, so
     * the scan has to match every spelling — otherwise those companies never get a reminder.
     */
    #[TestWith(['03'])]
    #[TestWith([' 3'])]
    #[TestWith(['3 days'])]
    public function testGetInvoicesNeedingPreDueRemindersMatchesNonCanonicalDaySettings(string $storedValue): void
    {
        $this->updateSetting('invoice/reminder/pre_due_days', $storedValue);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $this->clock->now()->modify('+3 days'),
        ]);

        self::assertSame([3], array_keys($this->repository->getConfiguredPreDueDays()));

        $results = iterator_to_array($this->repository->getInvoicesNeedingPreDueReminders(3));

        self::assertCount(1, $results);
        self::assertSame($invoice->getId()->toBase32(), $results[0]['invoiceId']->toBase32());
    }

    /**
     * Two companies configuring the same window with different spellings is what makes the scan
     * match against a list rather than a single value. Worth its own case: every other test here
     * configures one company, where a list of one and a plain scalar would behave identically, so
     * nothing else would notice if the IN () parameter stopped expanding.
     */
    public function testGetInvoicesNeedingPreDueRemindersMatchesEverySpellingOfTheSameWindow(): void
    {
        $dueDate = $this->clock->now()->modify('+3 days');

        $this->updateSetting('invoice/reminder/pre_due_days', '3');

        $canonical = InvoiceFactory::createOne([
            'company' => $this->company,
            'status' => InvoiceStatus::Pending,
            'due' => $dueDate,
        ]);

        // Creating a company seeds its default reminder settings and switches the active tenant.
        $otherCompany = CompanyFactory::createOne();
        $this->updateSetting('invoice/reminder/pre_due_days', '03', $otherCompany);

        $nonCanonical = InvoiceFactory::createOne([
            'company' => $otherCompany,
            'status' => InvoiceStatus::Pending,
            'due' => $dueDate,
        ]);

        $results = $this->withoutCompanyFilter(
            fn (): array => iterator_to_array($this->repository->getInvoicesNeedingPreDueReminders(3))
        );

        $found = array_map(static fn (array $row): string => $row['invoiceId']->toBase32(), $results);

        sort($found);
        $expected = [$canonical->getId()->toBase32(), $nonCanonical->getId()->toBase32()];
        sort($expected);

        self::assertSame($expected, $found, 'Both spellings of the same window must be scanned');
    }

    /**
     * Switching either reminder toggle off leaves the day setting behind, so counting every stored
     * window would keep the disabled company's window in the list and the caller would run a scan
     * for it on every cron tick — one that can never match, since the scan itself requires both
     * toggles to be on.
     */
    #[TestWith(['invoice/reminder/enabled'])]
    #[TestWith(['invoice/reminder/pre_due_enabled'])]
    public function testGetConfiguredPreDueDaysExcludesCompaniesWithRemindersDisabled(string $disabledToggle): void
    {
        $this->updateSetting($disabledToggle, '0');

        self::assertSame([], $this->repository->getConfiguredPreDueDays());
    }

    /**
     * The windows are collected across the whole tenant base, so a window no enabled company shares
     * has to drop out entirely — while the windows of the companies that are still on stay.
     */
    public function testGetConfiguredPreDueDaysOnlyReturnsWindowsFromCompaniesWithRemindersOn(): void
    {
        // Creating a company seeds its default reminder settings and switches the active tenant to it.
        $disabledCompany = CompanyFactory::createOne();

        $this->updateSetting('invoice/reminder/pre_due_days', '7', $disabledCompany);
        $this->updateSetting('invoice/reminder/enabled', '0', $disabledCompany);

        self::assertSame(
            [3],
            array_keys($this->withoutCompanyFilter(fn (): array => $this->repository->getConfiguredPreDueDays()))
        );
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
        self::assertSame($invoice->getId()->toBase32(), $results[0]['invoiceId']->toBase32());
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
        self::assertSame($invoice7Days->getId()->toBase32(), $results[0]['invoiceId']->toBase32());
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
        self::assertSame($overdueInvoice->getId()->toBase32(), $results[0]['invoiceId']->toBase32());
    }

    public function testCountCreatedInMonthCountsInvoicesInTheCalendarMonth(): void
    {
        $month = new DateTimeImmutable('2024-02-15 10:00:00', new DateTimeZone('UTC'));

        InvoiceFactory::createMany(3, [
            'company' => $this->company,
            'created' => new DateTimeImmutable('2024-02-10 10:00:00', new DateTimeZone('UTC')),
        ]);

        InvoiceFactory::createOne([
            'company' => $this->company,
            'created' => new DateTimeImmutable('2024-02-28 23:00:00', new DateTimeZone('UTC')),
        ]);

        // Outside the month (should be excluded)
        InvoiceFactory::createOne([
            'company' => $this->company,
            'created' => new DateTimeImmutable('2024-01-31 23:00:00', new DateTimeZone('UTC')),
        ]);
        InvoiceFactory::createOne([
            'company' => $this->company,
            'created' => new DateTimeImmutable('2024-03-01 00:00:00', new DateTimeZone('UTC')),
        ]);

        self::assertSame(4, $this->repository->countCreatedInMonth($month));
    }

    public function testCountCreatedInMonthReturnsZeroWhenNoInvoicesExist(): void
    {
        $month = new DateTimeImmutable('2024-04-15 10:00:00', new DateTimeZone('UTC'));

        self::assertSame(0, $this->repository->countCreatedInMonth($month));
    }

    private function updateSetting(string $key, string $value, ?Company $company = null): void
    {
        $company ??= $this->company;

        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $setting = $entityManager->getRepository(Setting::class)
            ->findOneBy([
                'company' => $company,
                'key' => $key,
            ]);

        if ($setting === null) {
            $setting = new Setting();
            $setting->setKey($key);
            $setting->setCompany($company);
            $entityManager->persist($setting);
        }

        $setting->setValue($value);
        $entityManager->flush();
    }

    /**
     * The reminder scans run across every tenant at once, which SendInvoiceRemindersCommand sets up
     * by suspending the company filter. Without that the query only ever sees the active company.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    private function withoutCompanyFilter(callable $callback): mixed
    {
        $filters = self::getContainer()->get(EntityManagerInterface::class)->getFilters();
        $enabled = $filters->isEnabled('company');

        if ($enabled) {
            $filters->suspend('company');
        }

        try {
            return $callback();
        } finally {
            if ($enabled) {
                $filters->restore('company');
            }
        }
    }
}
