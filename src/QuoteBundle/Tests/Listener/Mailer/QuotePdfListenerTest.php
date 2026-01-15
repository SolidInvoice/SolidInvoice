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

namespace SolidInvoice\QuoteBundle\Tests\Listener\Mailer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuotePdfListener;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Part\DataPart;
use Twig\Environment;

class QuotePdfListenerTest extends TestCase
{
    public function testListener(): void
    {
        $quote = new Quote();

        /** @var MailerInterface&MockObject $mailer */
        $mailer = $this->createMock(MailerInterface::class);

        /** @var Environment&MockObject $twig */
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $quote])
            ->willReturn('<p>Quote #1</p>');

        /** @var Generator&MockObject $pdf */
        $pdf = $this->createMock(Generator::class);
        $pdf->method('canPrintPdf')
            ->willReturn(true);

        $pdf->method('generate')
            ->with('<p>Quote #1</p>')
            ->willReturn('PDF: Quote #1');

        $listener = new QuotePdfListener($pdf, $twig);

        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        self::assertEquals(
            [new DataPart('PDF: Quote #1', "quote_{$quote->getId()}.pdf", 'application/pdf')],
            $message->getAttachments()
        );
    }

    public function testEvents(): void
    {
        self::assertSame([MessageEvent::class], \array_keys(QuotePdfListener::getSubscribedEvents()));
    }
}
