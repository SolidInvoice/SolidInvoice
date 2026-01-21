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

namespace SolidInvoice\InvoiceBundle\Listener;

use Psr\Log\LoggerInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Notification\InvoiceOverdueNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Listens to invoice workflow transitions and sends notifications to internal users
 * when an invoice becomes overdue. Client emails are handled by the invoice reminder
 * system (see SendInvoiceRemindersCommand).
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\InvoiceOverdueListenerTest
 */
final readonly class InvoiceOverdueListener implements EventSubscriberInterface
{
    public function __construct(
        private NotificationManager $notificationManager,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'workflow.invoice.entered.overdue' => 'onInvoiceOverdue',
        ];
    }

    public function onInvoiceOverdue(Event $event): void
    {
        $invoice = $event->getSubject();

        if (! $invoice instanceof Invoice) {
            return;
        }

        // Send notification to internal users who subscribed
        // Note: Client emails are handled by the invoice reminder system (1, 7, 14 days overdue)
        try {
            $this->notificationManager->sendNotification(
                new InvoiceOverdueNotification([
                    'invoice' => $invoice,
                    'client' => $invoice->getClient(),
                ])
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send overdue notification to users', [
                'invoice_id' => $invoice->getInvoiceId(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
