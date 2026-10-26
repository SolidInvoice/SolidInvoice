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

use BackedEnum;
use Override;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;
use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Twig\Environment;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Notification\InvoiceReminderNotificationTest
 */
#[AsNotification(
    name: self::EVENT,
    title: 'notifications.events.invoice_reminder.title',
    description: 'notifications.events.invoice_reminder.description',
    icon: 'tabler:clock',
    category: NotificationCategory::INVOICE,
)]
class InvoiceReminderNotification extends NotificationMessage
{
    public const EVENT = 'invoice_reminder';

    final public const string HTML_TEMPLATE = '@SolidInvoiceInvoice/Email/reminder.html.twig';

    final public const string TEXT_TEMPLATE = '@SolidInvoiceInvoice/Email/reminder.text.twig';

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
        if (isset($parameters['reminder_type']) && $parameters['reminder_type'] instanceof BackedEnum) {
            $parameters['reminder_type'] = $parameters['reminder_type']->value;
        }

        return $parameters;
    }

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getNormalizedParameters());
    }

    #[Override]
    public function getSubjectKey(): string
    {
        return match ($this->getNormalizedParameters()['reminder_type'] ?? '') {
            'pre_due' => 'invoice.reminder.subject.pre_due',
            'overdue_1' => 'invoice.reminder.subject.overdue_1',
            'overdue_7' => 'invoice.reminder.subject.overdue_7',
            'overdue_14' => 'invoice.reminder.subject.overdue_14',
            default => 'invoice.reminder.subject.default',
        };
    }

    #[Override]
    public function getSubjectParameters(): array
    {
        return ['%id%' => (string) ($this->getParameters()['invoice']?->getInvoiceId() ?? '')];
    }

    #[Override]
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
            $importance = $reminderType == 'overdue_14'
                ? NotificationEmail::IMPORTANCE_URGENT
                : NotificationEmail::IMPORTANCE_MEDIUM;
            $email->importance($importance);
        }

        return $message;
    }
}
