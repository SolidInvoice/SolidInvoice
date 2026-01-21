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

namespace SolidInvoice\InvoiceBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Message\SendInvoiceReminderMessage;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidWorx\Platform\PlatformBundle\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;
use function assert;
use function sprintf;

#[AsCommand(
    name: 'solidinvoice:invoices:send-reminders',
    description: 'Send payment reminders for pending and overdue invoices',
)]
#[AsScheduledTask('0 9 * * *')] // Daily at 9 AM
final class SendInvoiceRemindersCommand extends Command
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly InvoiceRepository $invoiceRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function handle(): int
    {
        $entityManager = $this->registry->getManagerForClass(Invoice::class);
        assert($entityManager instanceof EntityManagerInterface);

        // Disable company filter to query across all companies
        $filters = $entityManager->getFilters();
        $companyFilterEnabled = $filters->isEnabled('company');

        if ($companyFilterEnabled) {
            $filters->disable('company');
        }

        $preDueCount = 0;
        $overdueCount = 0;

        try {
            // Dispatch pre-due reminder messages
            $preDueCount = $this->dispatchPreDueReminders();

            // Dispatch overdue reminder messages
            $overdueCount = $this->dispatchOverdueReminders();
        } finally {
            // Re-enable company filter if it was enabled
            if ($companyFilterEnabled) {
                $filters->enable('company');
            }
        }

        $this->io->success(sprintf(
            'Dispatched %d pre-due reminder messages and %d overdue reminder messages',
            $preDueCount,
            $overdueCount
        ));

        return self::SUCCESS;
    }

    private function dispatchPreDueReminders(): int
    {
        $count = 0;

        $this->io->writeln('Processing pre-due reminders (3 days before due)...');

        // Query all invoices needing pre-due reminders across all companies
        // Company-specific settings will be checked in the message handler
        foreach ($this->invoiceRepository->getInvoicesNeedingPreDueReminders(3) as $invoice) {
            $daysUntilDue = null;
            if ($invoice->getDue()) {
                $interval = $this->clock->now()->diff($invoice->getDue());
                $daysUntilDue = $interval->days !== false ? (int) $interval->days : null;
            }

            $this->messageBus->dispatch(
                new SendInvoiceReminderMessage(
                    $invoice->getId(),
                    $invoice->getCompany()->getId(),
                    ReminderType::PreDue,
                    $daysUntilDue
                )
            );

            ++$count;
        }

        return $count;
    }

    private function dispatchOverdueReminders(): int
    {
        $count = 0;

        // Overdue 1 day reminder
        $this->io->writeln('Processing 1-day overdue reminders...');
        foreach ($this->invoiceRepository->getInvoicesNeedingOverdueReminders(1, ReminderType::Overdue1) as $invoice) {
            $this->messageBus->dispatch(
                new SendInvoiceReminderMessage(
                    $invoice->getId(),
                    $invoice->getCompany()->getId(),
                    ReminderType::Overdue1
                )
            );

            ++$count;
        }

        // Overdue 7 days reminder
        $this->io->writeln('Processing 7-day overdue reminders...');
        foreach ($this->invoiceRepository->getInvoicesNeedingOverdueReminders(7, ReminderType::Overdue7) as $invoice) {
            $this->messageBus->dispatch(
                new SendInvoiceReminderMessage(
                    $invoice->getId(),
                    $invoice->getCompany()->getId(),
                    ReminderType::Overdue7
                )
            );

            ++$count;
        }

        // Overdue 14 days reminder (final automated reminder)
        $this->io->writeln('Processing 14-day overdue reminders (final automated reminder)...');
        foreach ($this->invoiceRepository->getInvoicesNeedingOverdueReminders(14, ReminderType::Overdue14) as $invoice) {
            $this->messageBus->dispatch(
                new SendInvoiceReminderMessage(
                    $invoice->getId(),
                    $invoice->getCompany()->getId(),
                    ReminderType::Overdue14
                )
            );

            ++$count;
        }

        return $count;
    }
}
