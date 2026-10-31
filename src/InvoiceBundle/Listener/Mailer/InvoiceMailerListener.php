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

use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Traits\FlashErrorTrait;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Mailer\InvoiceMailerListenerTest
 */
class InvoiceMailerListener
{
    use FlashErrorTrait;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsEventListener(event: InvoiceEvents::INVOICE_POST_ACCEPT)]
    public function onInvoiceAccepted(InvoiceEvent $event): void
    {
        $invoice = $event->getInvoice();

        if (! $invoice instanceof Invoice) {
            return;
        }

        if ($invoice->getUsers()->isEmpty()) {
            $this->logger->warning('Cannot send invoice email: invoice has no recipients', [
                'invoice_id' => (string) $invoice->getId(),
            ]);
            return;
        }

        try {
            $this->mailer->send(new InvoiceEmail($invoice));
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send invoice email: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $this->addFlashError('invoice.email.send_failed');
        }
    }
}
