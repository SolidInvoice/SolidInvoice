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

namespace SolidInvoice\InvoiceBundle\Tests\Listener;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\InvoiceOverdueListener;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

/** @covers \SolidInvoice\InvoiceBundle\Listener\InvoiceOverdueListener */
final class InvoiceOverdueListenerTest extends TestCase
{
    public function testListenerSendsNotificationAndEmail(): void
    {
        $client = (new Client())->setName('Test Client')->setCurrencyCode('USD');
        $contact = (new Contact())->setEmail('client@example.com');

        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_OVERDUE);
        $invoice->setClient($client);
        $invoice->addUser($contact);
        $invoice->setInvoiceId('INV-001');

        /** @var NotificationManager&MockObject $notificationManager */
        $notificationManager = $this->createMock(NotificationManager::class);
        $notificationManager->expects($this->once())
            ->method('sendNotification');

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) {
                return $email->getTo()[0]->getAddress() === 'client@example.com';
            }));

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info')
            ->with('Overdue email sent to client', $this->anything());

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        /** @var WorkflowInterface&MockObject $workflow */
        $workflow = $this->createMock(WorkflowInterface::class);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            $workflow
        );

        $listener->onInvoiceOverdue($event);
    }

    public function testListenerHandlesInvoiceWithoutClient(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_OVERDUE);
        $invoice->setInvoiceId('INV-001');
        // No client set

        /** @var NotificationManager&MockObject $notificationManager */
        $notificationManager = $this->createMock(NotificationManager::class);
        $notificationManager->expects($this->once())
            ->method('sendNotification');

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Cannot send overdue email: invoice has no client', $this->anything());

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        /** @var WorkflowInterface&MockObject $workflow */
        $workflow = $this->createMock(WorkflowInterface::class);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            $workflow
        );

        $listener->onInvoiceOverdue($event);
    }

    public function testListenerHandlesInvoiceWithoutContacts(): void
    {
        $client = (new Client())->setName('Test Client')->setCurrencyCode('USD');

        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_OVERDUE);
        $invoice->setClient($client);
        $invoice->setInvoiceId('INV-001');
        // No contacts

        /** @var NotificationManager&MockObject $notificationManager */
        $notificationManager = $this->createMock(NotificationManager::class);
        $notificationManager->expects($this->once())
            ->method('sendNotification');

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->never())
            ->method('send');

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Cannot send overdue email: invoice has no contacts', $this->anything());

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        /** @var WorkflowInterface&MockObject $workflow */
        $workflow = $this->createMock(WorkflowInterface::class);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            $workflow
        );

        $listener->onInvoiceOverdue($event);
    }

    public function testListenerHandlesMultipleContacts(): void
    {
        $client = (new Client())->setName('Test Client')->setCurrencyCode('USD');
        $contact1 = (new Contact())->setEmail('contact1@example.com');
        $contact2 = (new Contact())->setEmail('contact2@example.com');

        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_OVERDUE);
        $invoice->setClient($client);
        $invoice->addUser($contact1);
        $invoice->addUser($contact2);
        $invoice->setInvoiceId('INV-001');

        /** @var NotificationManager&MockObject $notificationManager */
        $notificationManager = $this->createMock(NotificationManager::class);
        $notificationManager->expects($this->once())
            ->method('sendNotification');

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->callback(function ($email) {
                $addresses = array_map(fn ($addr) => $addr->getAddress(), $email->getTo());
                return in_array('contact1@example.com', $addresses) && in_array('contact2@example.com', $addresses);
            }));

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('info');

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        /** @var WorkflowInterface&MockObject $workflow */
        $workflow = $this->createMock(WorkflowInterface::class);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            $workflow
        );

        $listener->onInvoiceOverdue($event);
    }

    public function testListenerHandlesNotificationFailure(): void
    {
        $client = (new Client())->setName('Test Client')->setCurrencyCode('USD');
        $contact = (new Contact())->setEmail('client@example.com');

        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_OVERDUE);
        $invoice->setClient($client);
        $invoice->addUser($contact);
        $invoice->setInvoiceId('INV-001');

        /** @var NotificationManager&MockObject $notificationManager */
        $notificationManager = $this->createMock(NotificationManager::class);
        $notificationManager->expects($this->once())
            ->method('sendNotification')
            ->willThrowException(new \RuntimeException('Notification failed'));

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send');

        /** @var LoggerInterface&MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with('Failed to send overdue notification to users', $this->anything());
        $logger->expects($this->once())
            ->method('info');

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        /** @var WorkflowInterface&MockObject $workflow */
        $workflow = $this->createMock(WorkflowInterface::class);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            $workflow
        );

        $listener->onInvoiceOverdue($event);
    }
}
