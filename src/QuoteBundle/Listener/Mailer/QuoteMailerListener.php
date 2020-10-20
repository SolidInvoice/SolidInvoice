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

namespace SolidInvoice\QuoteBundle\Listener\Mailer;

use SolidInvoice\MailerBundle\MailerInterface;
use SolidInvoice\MailerBundle\Mailer;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Event\QuoteEvent;
use SolidInvoice\QuoteBundle\Event\QuoteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuoteMailerListener implements EventSubscriberInterface
{
    /**
     * @var Mailer
     */
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            QuoteEvents::QUOTE_POST_SEND => 'onQuoteSend',
        ];
    }

    public function onQuoteSend(QuoteEvent $event)
    {
        $this->mailer->send(new QuoteEmail($event->getQuote()));
    }
}
