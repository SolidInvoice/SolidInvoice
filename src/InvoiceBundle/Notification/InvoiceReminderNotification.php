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

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    public function getSubject(): string
    {
        $parameters = $this->getParameters();
        $reminderType = $parameters['reminder_type'] ?? '';
        $invoiceId = $parameters['invoice']?->getInvoiceId() ?? '';

        // Convert enum to string value if needed
        $typeValue = $reminderType instanceof \BackedEnum ? $reminderType->value : $reminderType;

        return match ($typeValue) {
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
            $email->textTemplate(self::TEXT_TEMPLATE);
            $email->htmlTemplate(self::HTML_TEMPLATE);
            $email->context($this->getParameters());

            $reminderType = $this->getParameters()['reminder_type'] ?? '';
            // Convert enum to string value if needed
            $typeValue = $reminderType instanceof \BackedEnum ? $reminderType->value : $reminderType;
            $importance = in_array($typeValue, ['overdue_14'])
                ? NotificationEmail::IMPORTANCE_URGENT
                : NotificationEmail::IMPORTANCE_MEDIUM;
            $email->importance($importance);
        }

        return $message;
    }
}
