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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
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
    use MockeryPHPUnitIntegration;

    public function testListenerSendsNotificationAndEmail(): void
    {
        $client = (new Client())->setName('Test Client')->setCurrencyCode('USD');
        $contact = (new Contact())->setEmail('client@example.com');

        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_OVERDUE);
        $invoice->setClient($client);
        $invoice->addUser($contact);
        $invoice->setInvoiceId('INV-001');

        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldReceive('sendNotification')
            ->once();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send')
            ->once()
            ->with(M::on(function ($email) {
                return $email->getTo()[0]->getAddress() === 'client@example.com';
            }));

        $logger = M::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info')
            ->once()
            ->with('Overdue email sent to client', M::any());

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            M::mock(WorkflowInterface::class)
        );

        $listener->onInvoiceOverdue($event);
    }

    public function testListenerHandlesInvoiceWithoutClient(): void
    {
        $invoice = new Invoice();
        $invoice->setStatus(Graph::STATUS_OVERDUE);
        $invoice->setInvoiceId('INV-001');
        // No client set

        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldReceive('sendNotification')
            ->once();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldNotReceive('send');

        $logger = M::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with('Cannot send overdue email: invoice has no client', M::any());

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            M::mock(WorkflowInterface::class)
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

        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldReceive('sendNotification')
            ->once();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldNotReceive('send');

        $logger = M::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('warning')
            ->once()
            ->with('Cannot send overdue email: invoice has no contacts', M::any());

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            M::mock(WorkflowInterface::class)
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

        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldReceive('sendNotification')
            ->once();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send')
            ->once()
            ->with(M::on(function ($email) {
                $addresses = array_map(fn ($addr) => $addr->getAddress(), $email->getTo());
                return in_array('contact1@example.com', $addresses) && in_array('contact2@example.com', $addresses);
            }));

        $logger = M::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('info')
            ->once();

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            M::mock(WorkflowInterface::class)
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

        $notificationManager = M::mock(NotificationManager::class);
        $notificationManager->shouldReceive('sendNotification')
            ->once()
            ->andThrow(new \RuntimeException('Notification failed'));

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send')
            ->once();

        $logger = M::mock(\Psr\Log\LoggerInterface::class);
        $logger->shouldReceive('error')
            ->once()
            ->with('Failed to send overdue notification to users', M::any());
        $logger->shouldReceive('info')
            ->once();

        $listener = new InvoiceOverdueListener($mailer, $notificationManager, $logger);

        $event = new Event(
            $invoice,
            new Marking([Graph::STATUS_OVERDUE => 1]),
            new Transition(Graph::TRANSITION_OVERDUE, Graph::STATUS_PENDING, Graph::STATUS_OVERDUE),
            M::mock(WorkflowInterface::class)
        );

        $listener->onInvoiceOverdue($event);
    }
}
