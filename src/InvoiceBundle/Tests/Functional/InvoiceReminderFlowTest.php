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

namespace SolidInvoice\InvoiceBundle\Tests\Functional;

use DateTimeImmutable;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\InvoiceBundle\Command\SendInvoiceRemindersCommand;
use SolidInvoice\InvoiceBundle\Entity\ReminderStatus;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidInvoice\SettingsBundle\Repository\SettingsRepository;
use SolidWorx\Platform\PlatformBundle\Console\IO;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;

/**
 * @covers \SolidInvoice\InvoiceBundle\Command\SendInvoiceRemindersCommand
 * @covers \SolidInvoice\InvoiceBundle\Repository\InvoiceRepository
 * @covers \SolidInvoice\InvoiceBundle\Repository\InvoiceReminderRepository
 * @covers \SolidInvoice\InvoiceBundle\Notification\InvoiceReminderNotification
 * @covers \SolidInvoice\InvoiceBundle\Notification\InvoiceReminderStoppedNotification
 */
final class InvoiceReminderFlowTest extends KernelTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    private InvoiceReminderRepository $reminderRepository;

    private SettingsRepository $settingRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reminderRepository = self::getContainer()->get(InvoiceReminderRepository::class);
        $this->settingRepository = self::getContainer()->get(SettingsRepository::class);
    }

    public function testCompleteReminderFlowFromPreDueToFinalAutomatedReminder(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        // Create invoice due in 3 days
        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => (new DateTimeImmutable())->modify('+3 days'),
            'users' => [$contact],
        ]);

        // Enable reminders
        $this->enableReminders();

        // Step 1: Pre-due reminder (3 days before)
        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(1, $reminders);
        self::assertSame(ReminderType::PreDue, $reminders[0]->getReminderType());
        self::assertSame(ReminderStatus::Sent, $reminders[0]->getStatus());
        self::assertNotNull($reminders[0]->getSentAt());
        self::assertNull($reminders[0]->getFailureReason());

        // Step 2: Update invoice to 1 day overdue
        $invoice->setDue((new DateTimeImmutable())->modify('-1 day'));
        $invoice->_save();

        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(2, $reminders);
        self::assertSame(ReminderType::Overdue1, $reminders[1]->getReminderType());

        // Step 3: Update invoice to 7 days overdue
        $invoice->setDue((new DateTimeImmutable())->modify('-7 days'));
        $invoice->_save();

        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(3, $reminders);
        self::assertSame(ReminderType::Overdue7, $reminders[2]->getReminderType());

        // Step 4: Update invoice to 14 days overdue (final automated reminder)
        $invoice->setDue((new DateTimeImmutable())->modify('-14 days'));
        $invoice->_save();

        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(4, $reminders);
        self::assertSame(ReminderType::Overdue14, $reminders[3]->getReminderType());

        // Step 5: Update invoice to 30 days overdue (no more automated reminders)
        $invoice->setDue((new DateTimeImmutable())->modify('-30 days'));
        $invoice->_save();

        $this->runCommand();

        // Should still have only 4 reminders (no more automated reminders after day 14)
        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(4, $reminders);
    }

    public function testReminderNotSentWhenGloballyDisabled(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => (new DateTimeImmutable())->modify('+3 days'),
            'users' => [$contact],
        ]);

        // Disable reminders
        $this->disableReminders();

        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(0, $reminders);
    }

    public function testPreDueReminderNotSentWhenDisabled(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => (new DateTimeImmutable())->modify('+3 days'),
            'users' => [$contact],
        ]);

        // Enable reminders but disable pre-due
        $this->enableReminders();
        $this->disablePreDueReminders();

        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(0, $reminders);
    }

    public function testDuplicateRemindersNotSent(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => (new DateTimeImmutable())->modify('+3 days'),
            'users' => [$contact],
        ]);

        $this->enableReminders();

        // Run command twice
        $this->runCommand();
        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(1, $reminders, 'Should only send one reminder, not duplicate');
    }

    public function testNoRemindersForPaidInvoices(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Paid,
            'due' => (new DateTimeImmutable())->modify('+3 days'),
            'users' => [$contact],
        ]);

        $this->enableReminders();

        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(0, $reminders);
    }

    public function testNoRemindersForInvoicesWithoutContacts(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);

        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => (new DateTimeImmutable())->modify('+3 days'),
            'users' => [], // No contacts
        ]);

        $this->enableReminders();

        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(0, $reminders);
    }

    public function testIdempotencyGuardPreventsReminderDuplication(): void
    {
        $client = ClientFactory::createOne(['company' => $this->company, 'currencyCode' => 'USD']);
        $contact = ContactFactory::createOne(['client' => $client, 'company' => $this->company]);

        // Create invoice due in 3 days
        $invoice = InvoiceFactory::createOne([
            'company' => $this->company,
            'client' => $client,
            'status' => InvoiceStatus::Pending,
            'due' => (new DateTimeImmutable())->modify('+3 days'),
            'users' => [$contact],
        ]);

        $this->enableReminders();

        // First run should create one reminder
        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(1, $reminders);
        self::assertSame(ReminderType::PreDue, $reminders[0]->getReminderType());

        // Second run with same invoice state should not create duplicate
        // (idempotency guard should prevent it)
        $this->runCommand();

        $reminders = $this->reminderRepository->getReminderHistory($invoice->_real());
        self::assertCount(1, $reminders, 'Idempotency guard should prevent duplicate reminder');
    }

    private function runCommand(): void
    {
        // Get the command directly from the container
        $command = self::getContainer()->get(SendInvoiceRemindersCommand::class);

        // Manually set IO object for Platform Command
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command->setIo(new IO($input, $output));

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    private function enableReminders(): void
    {
        $this->updateSetting('invoice/reminder/enabled', '1');
        $this->updateSetting('invoice/reminder/pre_due_enabled', '1');
        $this->updateSetting('invoice/reminder/pre_due_days', '3');
    }

    private function disableReminders(): void
    {
        $this->updateSetting('invoice/reminder/enabled', '0');
    }

    private function disablePreDueReminders(): void
    {
        $this->updateSetting('invoice/reminder/pre_due_enabled', '0');
    }

    private function updateSetting(string $key, string $value): void
    {
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $setting = $this->settingRepository->findOneBy([
            'company' => $this->company,
            'key' => $key,
        ]);

        if ($setting === null) {
            // Create new setting if it doesn't exist
            $setting = new Setting();
            $setting->setKey($key);
            $setting->setCompany($this->company);
            $entityManager->persist($setting);
        }

        $setting->setValue($value);
        $entityManager->flush();
    }
}
