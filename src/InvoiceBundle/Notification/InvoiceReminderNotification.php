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

namespace SolidInvoice\InvoiceBundle\Notification;

use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;
use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Twig\Environment;

#[AsNotification(
    name: self::EVENT,
    title: 'Invoice Payment Reminder',
    description: 'Reminder sent to clients about upcoming or overdue invoices',
    icon: 'tabler:clock',
    category: NotificationCategory::INVOICE,
)]
class InvoiceReminderNotification extends NotificationMessage
{
    public const EVENT = 'invoice_reminder';

    final public const HTML_TEMPLATE = '@SolidInvoiceInvoice/Email/reminder.html.twig';

    final public const TEXT_TEMPLATE = '@SolidInvoiceInvoice/Email/reminder.text.twig';

    /**
     * Returns parameters with 'reminder_type' normalized to string value.
     * Converts BackedEnum instances to their string values for Twig templates and email context.
     *
     * @return array<string, mixed>
     */
    private function getNormalizedParameters(): array
    {
        $parameters = $this->getParameters();

        // Convert reminder_type enum to string value if needed
        if (isset($parameters['reminder_type']) && $parameters['reminder_type'] instanceof \BackedEnum) {
            $parameters['reminder_type'] = $parameters['reminder_type']->value;
        }

        return $parameters;
    }

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getNormalizedParameters());
    }

    public function getSubject(): string
    {
        $parameters = $this->getNormalizedParameters();
        $reminderType = $parameters['reminder_type'] ?? '';
        $invoiceId = $parameters['invoice']?->getInvoiceId() ?? '';

        return match ($reminderType) {
            'pre_due' => "Upcoming Payment Due: Invoice {$invoiceId}",
            'overdue_1' => "Payment Reminder: Invoice {$invoiceId}",
            'overdue_7' => "Payment Overdue: Invoice {$invoiceId}",
            'overdue_14' => "URGENT: Invoice {$invoiceId} - Immediate Action Required",
            default => "Invoice Payment Reminder: {$invoiceId}",
        };
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $message = parent::asEmailMessage($recipient, $transport);

        $email = $message->getMessage();

        if ($email instanceof NotificationEmail) {
            $normalizedParameters = $this->getNormalizedParameters();

            $email->textTemplate(self::TEXT_TEMPLATE);
            $email->htmlTemplate(self::HTML_TEMPLATE);
            $email->context($normalizedParameters);

            $reminderType = $normalizedParameters['reminder_type'] ?? '';
            $importance = in_array($reminderType, ['overdue_14'])
                ? NotificationEmail::IMPORTANCE_URGENT
                : NotificationEmail::IMPORTANCE_MEDIUM;
            $email->importance($importance);
        }

        return $message;
    }
}
