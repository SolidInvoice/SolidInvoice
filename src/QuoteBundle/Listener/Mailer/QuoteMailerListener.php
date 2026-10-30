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

use Psr\Log\LoggerInterface;
use SolidInvoice\CoreBundle\Traits\FlashErrorTrait;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Event\QuoteEvent;
use SolidInvoice\QuoteBundle\Event\QuoteEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Mailer\QuoteMailerListenerTest
 */
class QuoteMailerListener
{
    use FlashErrorTrait;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    #[AsEventListener(event: QuoteEvents::QUOTE_POST_SEND)]
    public function onQuoteSend(QuoteEvent $event): void
    {
        try {
            $this->mailer->send(new QuoteEmail($event->getQuote()));
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Failed to send quote email: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $this->addFlashError('quote.email.send_failed');
        }
    }
}
