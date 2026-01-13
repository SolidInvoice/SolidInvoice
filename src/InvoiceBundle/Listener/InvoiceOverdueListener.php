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
use SolidInvoice\InvoiceBundle\Email\InvoiceOverdueEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Notification\InvoiceOverdueNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\Event\Event;

/**
 * Listens to invoice workflow transitions and sends notifications
 * when an invoice becomes overdue.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\InvoiceOverdueListenerTest
 */
final readonly class InvoiceOverdueListener implements EventSubscriberInterface
{
    public function __construct(
        private MailerInterface $mailer,
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

        // Send email to client contacts
        $this->sendClientEmail($invoice);
    }

    private function sendClientEmail(Invoice $invoice): void
    {
        $client = $invoice->getClient();

        if (null === $client) {
            $this->logger->warning('Cannot send overdue email: invoice has no client', [
                'invoice_id' => $invoice->getInvoiceId(),
            ]);
            return;
        }

        $contacts = $invoice->getUsers();

        if ($contacts->isEmpty()) {
            $this->logger->warning('Cannot send overdue email: invoice has no contacts', [
                'invoice_id' => $invoice->getInvoiceId(),
                'client' => $client->getName(),
            ]);
            return;
        }

        $email = new InvoiceOverdueEmail($invoice);

        // Send to all invoice contacts
        $emailAddresses = [];
        foreach ($contacts as $contact) {
            if ($contact->getEmail()) {
                $emailAddresses[] = $contact->getEmail();
            }
        }

        if (empty($emailAddresses)) {
            $this->logger->warning('Cannot send overdue email: no valid email addresses', [
                'invoice_id' => $invoice->getInvoiceId(),
                'client' => $client->getName(),
            ]);
            return;
        }

        // Set recipients
        $email->to(...$emailAddresses);

        try {
            $this->mailer->send($email);

            $this->logger->info('Overdue email sent to client', [
                'invoice_id' => $invoice->getInvoiceId(),
                'client' => $client->getName(),
                'recipients' => $emailAddresses,
            ]);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send overdue email to client', [
                'invoice_id' => $invoice->getInvoiceId(),
                'client' => $client->getName(),
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
