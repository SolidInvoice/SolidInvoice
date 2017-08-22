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

namespace SolidInvoice\PaymentBundle\Listener;

use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\Notification\PaymentReceivedNotification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentReceivedListener implements EventSubscriberInterface
{
    /**
     * @var NotificationManager
     */
    private $notification;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PaymentEvents::PAYMENT_COMPLETE => 'onPaymentCapture',
        ];
    }

    /**
     * @param NotificationManager $notification
     */
    public function __construct(NotificationManager $notification)
    {
        $this->notification = $notification;
    }

    /**
     * @param PaymentCompleteEvent $event
     */
    public function onPaymentCapture(PaymentCompleteEvent $event)
    {
        $notification = new PaymentReceivedNotification(['payment' => $event->getPayment()]);

        $this->notification->sendNotification('payment_made', $notification);
    }
}
