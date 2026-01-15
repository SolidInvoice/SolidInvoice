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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoiceMailerListener;
use Symfony\Component\Mailer\MailerInterface;

class InvoiceMailerListenerTest extends TestCase
{
    public function testListener(): void
    {
        $invoice = new Invoice();

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(InvoiceEmail::class));

        $listener = new InvoiceMailerListener($mailer);

        $invoice->addUser((new Contact())->setEmail('another@example.com')->setFirstName('Another'));
        $listener->onInvoiceAccepted(new InvoiceEvent($invoice));
    }

    public function testEvents(): void
    {
        self::assertSame([InvoiceEvents::INVOICE_POST_ACCEPT], \array_keys(InvoiceMailerListener::getSubscribedEvents()));
    }
}
