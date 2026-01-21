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

namespace SolidInvoice\InvoiceBundle\MessageHandler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\InvoiceBundle\Email\InvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Message\SendInvoiceReminderMessage;
use SolidInvoice\InvoiceBundle\Notification\InvoiceReminderNotification;
use SolidInvoice\InvoiceBundle\Notification\InvoiceReminderStoppedNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function assert;

#[AsMessageHandler]
final readonly class SendInvoiceReminderHandler
{
    public function __construct(
        private ManagerRegistry $registry,
        private CompanySelector $companySelector,
        private MailerInterface $mailer,
        private NotificationManager $notificationManager,
        private SystemConfig $systemConfig,
        private ClockInterface $clock,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendInvoiceReminderMessage $message): void
    {
        $entityManager = $this->registry->getManagerForClass(Invoice::class);
        assert($entityManager instanceof EntityManagerInterface);

        try {
            // Set company context for this invoice
            $this->companySelector->switchCompany($message->companyId);

            // Check if reminders are enabled for this company
            if (! $this->isRemindersEnabled($message->reminderType)) {
                $this->logger->info('Invoice reminders are disabled for company', [
                    'company_id' => $message->companyId->toRfc4122(),
                    'invoice_id' => $message->invoiceId->toRfc4122(),
                    'reminder_type' => $message->reminderType->value,
                ]);
                return;
            }

            // Load the invoice
            $invoice = $entityManager->find(Invoice::class, $message->invoiceId);

            if (! $invoice instanceof Invoice) {
                $this->logger->warning('Invoice not found for reminder', [
                    'invoice_id' => $message->invoiceId->toRfc4122(),
                    'company_id' => $message->companyId->toRfc4122(),
                ]);
                return;
            }

            $this->sendReminder($invoice, $message, $entityManager);
        } finally {
            // Always reset company context, even if an error occurred
            $this->companySelector->reset();
        }
    }

    private function sendReminder(Invoice $invoice, SendInvoiceReminderMessage $message, EntityManagerInterface $entityManager): void
    {
        $contacts = $invoice->getUsers();

        if ($contacts->isEmpty()) {
            $this->logger->warning('Cannot send reminder: invoice has no contacts', [
                'invoice_id' => $invoice->getInvoiceId(),
                'company_id' => $message->companyId->toRfc4122(),
            ]);
            return;
        }

        // Send email to client contacts
        $email = new InvoiceReminderEmail($invoice, $message->reminderType, $message->daysUntilDue);

        try {
            $this->mailer->send($email);

            $this->logger->info('Sent reminder email to client', [
                'invoice_id' => $invoice->getInvoiceId(),
                'reminder_type' => $message->reminderType->value,
                'company_id' => $message->companyId->toRfc4122(),
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send reminder email to client', [
                'invoice_id' => $invoice->getInvoiceId(),
                'reminder_type' => $message->reminderType->value,
                'company_id' => $message->companyId->toRfc4122(),
                'exception' => $e->getMessage(),
            ]);
            // Don't return - still create reminder record and send internal notification
        }

        // Send notification to internal users (optional - they can subscribe if they want)
        try {
            $this->notificationManager->sendNotification(
                new InvoiceReminderNotification([
                    'invoice' => $invoice,
                    'reminder_type' => $message->reminderType,
                    'days_until_due' => $message->daysUntilDue,
                ])
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send reminder notification to users', [
                'invoice_id' => $invoice->getInvoiceId(),
                'reminder_type' => $message->reminderType->value,
                'company_id' => $message->companyId->toRfc4122(),
                'exception' => $e->getMessage(),
            ]);
        }

        // Create reminder record
        $reminder = new InvoiceReminder();
        $reminder->setInvoice($invoice);
        $reminder->setReminderType($message->reminderType);
        $reminder->setCompany($invoice->getCompany());

        $entityManager->persist($reminder);
        $entityManager->flush();

        // Send escalation notification to internal users after final automated reminder
        if ($message->reminderType === ReminderType::Overdue14) {
            try {
                $daysOverdue = $this->calculateDaysOverdue($invoice);
                $this->notificationManager->sendNotification(
                    new InvoiceReminderStoppedNotification([
                        'invoice' => $invoice,
                        'days_overdue' => $daysOverdue,
                    ])
                );

                $this->logger->info('Sent escalation notification after final reminder', [
                    'invoice_id' => $invoice->getInvoiceId(),
                    'days_overdue' => $daysOverdue,
                    'company_id' => $message->companyId->toRfc4122(),
                ]);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send escalation notification', [
                    'invoice_id' => $invoice->getInvoiceId(),
                    'company_id' => $message->companyId->toRfc4122(),
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    private function calculateDaysOverdue(Invoice $invoice): int
    {
        if (! $invoice->getDue()) {
            return 0;
        }

        $interval = $this->clock->now()->diff($invoice->getDue());

        return $interval->days !== false ? (int) $interval->days : 0;
    }

    private function isRemindersEnabled(ReminderType $reminderType): bool
    {
        $enabled = $this->systemConfig->get('invoice/reminder/enabled');

        if ($enabled !== '1' && $enabled !== 'true') {
            return false;
        }

        // Check reminder-type-specific settings
        if ($reminderType === ReminderType::PreDue) {
            $preDueEnabled = $this->systemConfig->get('invoice/reminder/pre_due_enabled');
            return $preDueEnabled === '1' || $preDueEnabled === 'true';
        }

        // Overdue reminders are enabled if global reminders are enabled
        return true;
    }
}
