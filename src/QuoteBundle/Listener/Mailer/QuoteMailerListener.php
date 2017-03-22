<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Listener\Mailer;

use CSBill\CoreBundle\Mailer\Mailer;
use CSBill\QuoteBundle\Event\QuoteEvent;
use CSBill\QuoteBundle\Event\QuoteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QuoteMailerListener implements EventSubscriberInterface
{
    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @param Mailer $mailer
     */
    public function __construct(Mailer $mailer)
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

    /**
     * @param QuoteEvent $event
     */
    public function onQuoteSend(QuoteEvent $event)
    {
        $this->mailer->sendQuote($event->getQuote());
    }
}
