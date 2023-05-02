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

namespace SolidInvoice\InvoiceBundle\Listener\Mailer;

use Mpdf\MpdfException;
use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Mailer\InvoicePdfListenerTest
 */
class InvoicePdfListener implements EventSubscriberInterface
{
    public function __construct(private readonly Generator $generator, private readonly Environment $twig)
    {
    }

    /**
     * @throws MpdfException|LoaderError|RuntimeError|SyntaxError
     */
    public function __invoke(MessageEvent $event): void
    {
        /** @var InvoiceEmail $message */
        $message = $event->getMessage();

        if ($message instanceof InvoiceEmail && $this->generator->canPrintPdf()) {
            $content = $this->generator->generate(
                $this->twig->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $message->getInvoice()])
            );

            $message->attach($content, "invoice_{$message->getInvoice()->getId()}.pdf", 'application/pdf');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
