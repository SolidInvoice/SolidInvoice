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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoiceMailerListener;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoicePdfListener;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Environment;

class InvoicePdfListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testListener(): void
    {
        $invoice = new Invoice();

        $mailer = M::mock(MailerInterface::class);
        $mailer->shouldReceive('send');

        $twig = M::mock(Environment::class);
        $twig->shouldReceive('render')
            ->once()
            ->with('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $invoice])
            ->andReturn('<p>Invoice #1</p>');

        $pdf = M::mock(Generator::class);
        $pdf->shouldReceive('canPrintPdf')
            ->andReturnTrue();

        $pdf->shouldReceive('generate')
            ->with('<p>Invoice #1</p>')
            ->andReturn('PDF: Invoice #1');

        $listener = new InvoicePdfListener($pdf, $twig);

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals(
            [new DataPart('PDF: Invoice #1', 'invoice_.pdf', 'application/pdf')],
            $message->getAttachments()
        );
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(InvoicePdfListener::getSubscribedEvents()));
    }
}
