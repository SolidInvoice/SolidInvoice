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

use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Event\QuoteEvent;
use SolidInvoice\QuoteBundle\Event\QuoteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * @see \SolidInvoice\QuoteBundle\Tests\Listener\Mailer\QuoteMailerListenerTest
 */
class QuoteMailerListener implements EventSubscriberInterface
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            QuoteEvents::QUOTE_POST_SEND => 'onQuoteSend',
        ];
    }

    public function onQuoteSend(QuoteEvent $event): void
    {
        $this->mailer->send(new QuoteEmail($event->getQuote()));
    }
}
