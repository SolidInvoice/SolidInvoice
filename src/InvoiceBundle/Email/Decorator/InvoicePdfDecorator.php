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

namespace SolidInvoice\InvoiceBundle\Email\Decorator;

use SolidInvoice\CoreBundle\Pdf\Generator;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\MailerBundle\Decorator\MessageDecorator;
use SolidInvoice\MailerBundle\Decorator\VerificationMessageDecorator;
use SolidInvoice\MailerBundle\Event\MessageEvent;
use Swift_Attachment;
use Symfony\Component\Templating\EngineInterface;

final class InvoicePdfDecorator implements MessageDecorator, VerificationMessageDecorator
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
        /** @var InvoiceEmail $message */
        $message = $event->getMessage();

        $content = $this->generator->generate(
            $this->engine->render('@SolidInvoiceInvoice/Pdf/invoice.html.twig', ['invoice' => $message->getInvoice()])
        );
        $attachment = new Swift_Attachment($content, "invoice_{$message->getInvoice()->getId()}.pdf", 'application/pdf');

        $message->attach($attachment);
    }

    public function shouldDecorate(MessageEvent $event): bool
    {
        $message = $event->getMessage();

        return $message instanceof InvoiceEmail;
    }
}
