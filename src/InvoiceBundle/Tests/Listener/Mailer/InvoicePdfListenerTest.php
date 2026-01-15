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
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoicePdfListener;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Environment;

class InvoicePdfListenerTest extends TestCase
{
    public function testListener(): void
    {
        $invoice = new Invoice();

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $invoice])
            ->willReturn('<p>Invoice #1</p>');

        /** @var Generator&MockObject $pdf */
        $pdf = $this->createMock(Generator::class);
        $pdf->method('canPrintPdf')
            ->willReturn(true);

        $pdf->method('generate')
            ->with('<p>Invoice #1</p>')
            ->willReturn('PDF: Invoice #1');

        $listener = new InvoicePdfListener($pdf, $twig);

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals(
            [new DataPart('PDF: Invoice #1', "invoice_{$invoice->getId()}.pdf", 'application/pdf')],
            $message->getAttachments()
        );
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(InvoicePdfListener::getSubscribedEvents()));
    }
}
