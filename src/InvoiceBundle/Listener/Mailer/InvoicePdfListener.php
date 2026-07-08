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
use SolidInvoice\CoreBundle\Templates\BillingTemplateChannel;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
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
readonly class InvoicePdfListener implements EventSubscriberInterface
{
    public function __construct(
        private Generator $generator,
        private Environment $twig,
        private BillingTemplateResolver $templateResolver,
    ) {
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
                $this->twig->render($this->templateResolver->resolve($message->getInvoice(), BillingTemplateChannel::Pdf), ['invoice' => $message->getInvoice()])
            );

            $message->attach($content, sprintf('invoice_%s.pdf', $message->getInvoice()->getInvoiceId()), 'application/pdf');
        }
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            MessageEvent::class => '__invoke',
        ];
    }
}
