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

namespace SolidInvoice\InvoiceBundle\Tests\Listener\Mailer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Email\InvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Listener\Mailer\ReminderSubjectListener;
use Symfony\Component\Mailer\Event\MessageEvent;

/** @covers \SolidInvoice\InvoiceBundle\Listener\Mailer\ReminderSubjectListener */
final class ReminderSubjectListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListenerSetsPreDueSubject(): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-001');

        $email = new InvoiceReminderEmail($invoice, ReminderType::PreDue, 3);

        $listener = new ReminderSubjectListener();

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        self::assertSame('Upcoming Payment Due: Invoice INV-001', $email->getSubject());
    }

    public function testListenerSetsOverdue1Subject(): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-002');

        $email = new InvoiceReminderEmail($invoice, ReminderType::Overdue1);

        $listener = new ReminderSubjectListener();

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        self::assertSame('Payment Reminder: Invoice INV-002', $email->getSubject());
    }

    public function testListenerSetsOverdue7Subject(): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-003');

        $email = new InvoiceReminderEmail($invoice, ReminderType::Overdue7);

        $listener = new ReminderSubjectListener();

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        self::assertSame('Payment Overdue: Invoice INV-003', $email->getSubject());
    }

    public function testListenerSetsOverdue14Subject(): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-004');

        $email = new InvoiceReminderEmail($invoice, ReminderType::Overdue14);

        $listener = new ReminderSubjectListener();

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        self::assertSame('URGENT: Invoice INV-004 - Immediate Action Required', $email->getSubject());
    }

    public function testListenerIgnoresNonReminderEmails(): void
    {
        $email = M::mock(\Symfony\Component\Mime\Email::class);
        $email->shouldNotReceive('setSubject');

        $listener = new ReminderSubjectListener();

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);
    }

    public function testListenerSkipsWhenSubjectAlreadySet(): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-005');

        $email = new InvoiceReminderEmail($invoice, ReminderType::PreDue);
        $email->subject('Custom Subject');

        $listener = new ReminderSubjectListener();

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        // Should not modify existing subject
        self::assertSame('Custom Subject', $email->getSubject());
    }
}
