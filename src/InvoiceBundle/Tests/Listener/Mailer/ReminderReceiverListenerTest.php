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
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Email\InvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Listener\Mailer\ReminderReceiverListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;

/** @covers \SolidInvoice\InvoiceBundle\Listener\Mailer\ReminderReceiverListener */
final class ReminderReceiverListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListenerAddsRecipientsFromInvoiceContacts(): void
    {
        $contact1 = (new Contact())
            ->setEmail('contact1@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $contact2 = (new Contact())
            ->setEmail('contact2@example.com')
            ->setFirstName('Jane')
            ->setLastName('Smith');

        $invoice = new Invoice();
        $invoice->addUser($contact1);
        $invoice->addUser($contact2);
        $invoice->setInvoiceId('INV-001');

        $email = new InvoiceReminderEmail($invoice, ReminderType::PreDue);

        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->once()
            ->andReturn('');

        $listener = new ReminderReceiverListener($config);

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        $recipients = $email->getTo();
        self::assertCount(2, $recipients);

        $addresses = array_map(fn (Address $addr) => $addr->getAddress(), $recipients);
        self::assertContains('contact1@example.com', $addresses);
        self::assertContains('contact2@example.com', $addresses);
    }

    public function testListenerAddsBccWhenConfigured(): void
    {
        $contact = (new Contact())
            ->setEmail('contact@example.com')
            ->setFirstName('John')
            ->setLastName('Doe');

        $invoice = new Invoice();
        $invoice->addUser($contact);
        $invoice->setInvoiceId('INV-001');

        $email = new InvoiceReminderEmail($invoice, ReminderType::Overdue1);

        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->once()
            ->andReturn('bcc@example.com');

        $listener = new ReminderReceiverListener($config);

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        $bcc = $email->getBcc();
        self::assertCount(1, $bcc);
        self::assertSame('bcc@example.com', $bcc[0]->getAddress());
    }

    public function testListenerSkipsWhenRecipientsAlreadySet(): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-001');

        $email = new InvoiceReminderEmail($invoice, ReminderType::Overdue7);
        $email->addTo(new Address('existing@example.com'));

        $config = M::mock(SystemConfig::class);
        $config->shouldNotReceive('get');

        $listener = new ReminderReceiverListener($config);

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);

        // Should not modify existing recipients
        $recipients = $email->getTo();
        self::assertCount(1, $recipients);
        self::assertSame('existing@example.com', $recipients[0]->getAddress());
    }

    public function testListenerIgnoresNonReminderEmails(): void
    {
        $email = M::mock(\Symfony\Component\Mime\Email::class);

        $config = M::mock(SystemConfig::class);
        $config->shouldNotReceive('get');

        $listener = new ReminderReceiverListener($config);

        $event = new MessageEvent($email, M::mock(\Symfony\Component\Mailer\Envelope::class), 'smtp');

        $listener($event);
    }
}
