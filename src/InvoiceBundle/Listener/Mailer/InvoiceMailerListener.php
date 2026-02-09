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
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvent;
use SolidInvoice\InvoiceBundle\Event\InvoiceEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @see \SolidInvoice\InvoiceBundle\Tests\Listener\Mailer\InvoiceMailerListenerTest
 */
class InvoiceMailerListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvoiceEvents::INVOICE_POST_ACCEPT => 'onInvoiceAccepted',
        ];
    }

    public function onInvoiceAccepted(InvoiceEvent $event): void
    {
        try {
            $this->mailer->send(new InvoiceEmail($event->getInvoice()));
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send invoice email: ' . $e->getMessage(), [
                'exception' => $e,
                'invoiceId' => $event->getInvoice()->getId(),
            ]);
        }
    }
}
