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

use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceReminderFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

/** @covers \SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository */
final class InvoiceReminderRepositoryTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private InvoiceReminderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $registry = self::getContainer()->get('doctrine');
        $this->repository = $registry->getRepository(InvoiceReminder::class);
    }

    public function testHasReminderBeenSentReturnsTrueWhenReminderExists(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne(['company' => $company]);

        InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $company,
            'reminderType' => ReminderType::PreDue,
        ]);

        $result = $this->repository->hasReminderBeenSent($invoice->_real(), ReminderType::PreDue);

        self::assertTrue($result);
    }

    public function testHasReminderBeenSentReturnsFalseWhenReminderDoesNotExist(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne(['company' => $company]);

        $result = $this->repository->hasReminderBeenSent($invoice->_real(), ReminderType::PreDue);

        self::assertFalse($result);
    }

    public function testHasReminderBeenSentReturnsFalseForDifferentReminderType(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne(['company' => $company]);

        InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $company,
            'reminderType' => ReminderType::PreDue,
        ]);

        $result = $this->repository->hasReminderBeenSent($invoice->_real(), ReminderType::Overdue1);

        self::assertFalse($result);
    }

    public function testGetReminderHistoryReturnsAllRemindersForInvoice(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne(['company' => $company]);

        InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $company,
            'reminderType' => ReminderType::PreDue,
        ]);

        InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $company,
            'reminderType' => ReminderType::Overdue1,
        ]);

        InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $company,
            'reminderType' => ReminderType::Overdue7,
        ]);

        $history = $this->repository->getReminderHistory($invoice->_real());

        self::assertCount(3, $history);
        self::assertContainsOnlyInstancesOf(InvoiceReminder::class, $history);
    }

    public function testGetReminderHistoryReturnsEmptyArrayWhenNoReminders(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne(['company' => $company]);

        $history = $this->repository->getReminderHistory($invoice->_real());

        self::assertCount(0, $history);
    }

    public function testGetReminderHistoryOrdersBysentAtAscending(): void
    {
        $company = CompanyFactory::createOne();
        $invoice = InvoiceFactory::createOne(['company' => $company]);

        $reminder1 = InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $company,
            'reminderType' => ReminderType::PreDue,
            'sentAt' => new \DateTimeImmutable('2024-01-15'),
        ]);

        $reminder2 = InvoiceReminderFactory::createOne([
            'invoice' => $invoice,
            'company' => $company,
            'reminderType' => ReminderType::Overdue1,
            'sentAt' => new \DateTimeImmutable('2024-01-10'),
        ]);

        $history = $this->repository->getReminderHistory($invoice->_real());

        self::assertCount(2, $history);
        self::assertSame($reminder2->getId()->toBase32(), $history[0]->getId()->toBase32());
        self::assertSame($reminder1->getId()->toBase32(), $history[1]->getId()->toBase32());
    }

    public function testGetReminderHistoryDoesNotReturnRemindersFromOtherInvoices(): void
    {
        $company = CompanyFactory::createOne();
        $invoice1 = InvoiceFactory::createOne(['company' => $company]);
        $invoice2 = InvoiceFactory::createOne(['company' => $company]);

        InvoiceReminderFactory::createOne([
            'invoice' => $invoice1,
            'company' => $company,
            'reminderType' => ReminderType::PreDue,
        ]);

        InvoiceReminderFactory::createOne([
            'invoice' => $invoice2,
            'company' => $company,
            'reminderType' => ReminderType::PreDue,
        ]);

        $history = $this->repository->getReminderHistory($invoice1->_real());

        self::assertCount(1, $history);
    }
}
