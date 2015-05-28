<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Listener;

use CSBill\NotificationBundle\Notification\NotificationManager;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Notification\PaymentReceivedNotification;

class PaymentReceivedListener
{
    /**
     * @var NotificationManager
     */
    private $notification;

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
        $notification = new PaymentReceivedNotification(array('payment' => $event->getPayment()));

        $this->notification->sendNotification('payment_made', $notification);
    }
}
