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
    title: 'Invoice Requires Manual Follow-up',
    description: 'Notifies user after final automated reminder has been sent for an overdue invoice',
    icon: 'tabler:bell-off',
    category: NotificationCategory::INVOICE,
)]
class InvoiceReminderStoppedNotification extends NotificationMessage
{
    public const EVENT = 'invoice_reminder_stopped';

    final public const HTML_TEMPLATE = '@SolidInvoiceInvoice/Email/reminder_stopped.html.twig';

    final public const TEXT_TEMPLATE = '@SolidInvoiceInvoice/Email/reminder_stopped.text.twig';

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    public function getSubject(): string
    {
        $parameters = $this->getParameters();
        $invoiceId = $parameters['invoice']?->getInvoiceId() ?? '';

        return "Final Reminder Sent for Invoice {$invoiceId} - Manual Follow-up Required";
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $message = parent::asEmailMessage($recipient, $transport);

        $email = $message->getMessage();

        if ($email instanceof NotificationEmail) {
            $email->textTemplate(self::TEXT_TEMPLATE);
            $email->htmlTemplate(self::HTML_TEMPLATE);
            $email->context($this->getParameters());
            $email->importance(NotificationEmail::IMPORTANCE_HIGH);
        }

        return $message;
    }
}
