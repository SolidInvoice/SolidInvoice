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

namespace SolidInvoice\QuoteBundle\Email\Decorator;

use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\MailerBundle\Decorator\MessageDecorator;
use SolidInvoice\MailerBundle\Decorator\VerificationMessageDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use Swift_Attachment;
use Symfony\Component\Templating\EngineInterface;

final class QuotePdfDecorator implements MessageDecorator, VerificationMessageDecorator
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var EngineInterface
     */
    private $engine;

    public function __construct(Generator $generator, EngineInterface $engine)
    {
        $this->generator = $generator;
        $this->engine = $engine;
    }

    public function decorate(MessageEvent $event): void
    {
        /** @var QuoteEmail $message */
        $message = $event->getMessage();

        $content = $this->generator->generate(
            $this->engine->render('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $message->getQuote()])
        );
        $attachment = new Swift_Attachment($content, "quote_{$message->getQuote()->getId()}.pdf", 'application/pdf');

        $message->attach($attachment);
    }

    public function shouldDecorate(MessageEvent $event): bool
    {
        return $event->getMessage() instanceof QuoteEmail && $this->generator->canPrintPdf();
    }
}
