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

use Override;
use SolidInvoice\NotificationBundle\Attribute\AsNotification;
use SolidInvoice\NotificationBundle\Enum\NotificationCategory;
use SolidInvoice\NotificationBundle\Notification\NotificationMessage;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Twig\Environment;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Notification\RecurringInvoiceCreatedNotificationTest
 */
#[AsNotification(
    name: self::EVENT,
    title: 'notifications.events.recurring_invoice_created.title',
    description: 'notifications.events.recurring_invoice_created.description',
    icon: 'tabler:repeat',
    category: NotificationCategory::INVOICE,
)]
class RecurringInvoiceCreatedNotification extends NotificationMessage
{
    public const EVENT = 'recurring_invoice_created';

    final public const string HTML_TEMPLATE = '@SolidInvoiceInvoice/Email/recurring_created.html.twig';

    final public const string TEXT_TEMPLATE = '@SolidInvoiceInvoice/Email/recurring_created.txt.twig';

    public function getTextContent(Environment $twig): string
    {
        return $twig->render(self::TEXT_TEMPLATE, $this->getParameters());
    }

    #[Override]
    public function getSubjectKey(): string
    {
        return 'invoice.recurring_created.subject';
    }

    #[Override]
    public function getSubjectParameters(): array
    {
        return ['%id%' => (string) $this->getParameters()['invoice']->getInvoiceId()];
    }

    #[Override]
    public function asEmailMessage(EmailRecipientInterface $recipient, ?string $transport = null): EmailMessage
    {
        $message = parent::asEmailMessage($recipient, $transport);

        $email = $message->getMessage();

        if ($email instanceof NotificationEmail) {
            $email->textTemplate(self::TEXT_TEMPLATE);
            $email->htmlTemplate(self::HTML_TEMPLATE);
            $email->context($this->notificationEmailContext($this->getParameters()));
        }

        return $message;
    }
}
