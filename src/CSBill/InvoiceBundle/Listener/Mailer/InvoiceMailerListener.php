<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Listener\Mailer;

use CSBill\CoreBundle\Mailer\Mailer;
use CSBill\InvoiceBundle\Event\InvoiceEvent;
use CSBill\InvoiceBundle\Event\InvoiceEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InvoiceMailerListener implements EventSubscriberInterface
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
        return array(
            InvoiceEvents::INVOICE_POST_ACCEPT => 'onInvoiceAccepted',
        );
    }

    /**
     * @param InvoiceEvent $event
     */
    public function onInvoiceAccepted(InvoiceEvent $event)
    {
        $this->mailer->sendInvoice($event->getInvoice());
    }
}
