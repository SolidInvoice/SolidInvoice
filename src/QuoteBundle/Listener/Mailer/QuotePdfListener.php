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

namespace SolidInvoice\QuoteBundle\Listener\Mailer;

use Mpdf\MpdfException;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Mailer\QuotePdfListenerTest
 */
class QuotePdfListener implements EventSubscriberInterface
{
    /**
     * @var Generator
     */
    private $generator;

    /**
     * @var Environment
     */
    private $twig;

    public function __construct(Generator $generator, Environment $twig)
    {
        $this->generator = $generator;
        $this->twig = $twig;
    }

    /**
     * @throws MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    public function __invoke(MessageEvent $event): void
    {
        /** @var QuoteEmail $message */
        $message = $event->getMessage();

        if ($message instanceof QuoteEmail && $this->generator->canPrintPdf()) {
            $content = $this->generator->generate(
                $this->twig->render('@SolidInvoiceQuote/Pdf/quote.html.twig', ['quote' => $message->getQuote()])
            );

            $message->attach($content, "quote_{$message->getQuote()->getId()}.pdf", 'application/pdf');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
