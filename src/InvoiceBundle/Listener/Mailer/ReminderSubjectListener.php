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

namespace SolidInvoice\InvoiceBundle\Listener\Mailer;

use SolidInvoice\InvoiceBundle\Email\InvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;

class ReminderSubjectListener implements EventSubscriberInterface
{
    public function __invoke(MessageEvent $event): void
    {
        /** @var InvoiceReminderEmail $message */
        $message = $event->getMessage();

        if ($message instanceof InvoiceReminderEmail && null === $message->getSubject()) {
            $invoice = $message->getInvoice();
            $invoiceId = $invoice->getInvoiceId();
            $reminderType = $message->getReminderType();

            $subject = match ($reminderType) {
                ReminderType::PreDue => "Upcoming Payment Due: Invoice {$invoiceId}",
                ReminderType::Overdue1 => "Payment Reminder: Invoice {$invoiceId}",
                ReminderType::Overdue7 => "Payment Overdue: Invoice {$invoiceId}",
                ReminderType::Overdue14 => "URGENT: Invoice {$invoiceId} - Immediate Action Required",
            };

            $message->subject($subject);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
