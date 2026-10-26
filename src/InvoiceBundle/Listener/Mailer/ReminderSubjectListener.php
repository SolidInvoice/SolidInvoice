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
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Mailer\ReminderSubjectListenerTest
 */
class ReminderSubjectListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function __invoke(MessageEvent $event): void
    {
        $message = $event->getMessage();

        if (! $message instanceof InvoiceReminderEmail && ! $message instanceof ManualInvoiceReminderEmail || null !== $message->getSubject()) {
            return;
        }

        $key = $message instanceof InvoiceReminderEmail
            ? 'invoice.reminder.subject.' . $message->getReminderType()->value
            : 'invoice.manual_reminder.subject';

        $message->subject($this->translator->trans($key, ['%id%' => (string) $message->getInvoice()->getInvoiceId()], 'email'));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
