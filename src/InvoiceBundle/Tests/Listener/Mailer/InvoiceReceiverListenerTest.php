<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Tests\Listener\Mailer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoiceReceiverListener;
use SolidInvoice\SettingsBundle\SystemConfig;
use Swift_Message;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;

class InvoiceReceiverListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testWithoutBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->andReturnNull();

        $listener = new InvoiceReceiverListener($config);
        $invoice = new Invoice();
        $invoice->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $invoice->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        static::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        static::assertSame([], $message->getBcc());
    }

    public function testWithBcc(): void
    {
        $config = M::mock(SystemConfig::class);
        $config->shouldReceive('get')
            ->with('invoice/bcc_address')
            ->andReturn('bcc@example.com');

        $listener = new InvoiceReceiverListener($config);
        $invoice = new Invoice();
        $invoice->addUser((new Contact())->setEmail('test@example.com')->setFirstName('Test')->setLastName('User'));
        $invoice->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        static::assertEquals([new Address('test@example.com', 'Test User'), new Address('another@example.com', 'Another')], $message->getTo());
        static::assertEquals([new Address('bcc@example.com')], $message->getBcc());
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(InvoiceReceiverListener::getSubscribedEvents()));
    }
}
