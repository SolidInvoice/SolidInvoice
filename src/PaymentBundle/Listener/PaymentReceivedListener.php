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

namespace SolidInvoice\PaymentBundle\Listener;

use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\PaymentBundle\Notification\PaymentReceivedNotification;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class PaymentReceivedListener
{
    public function __construct(
        private readonly NotificationManager $notification
    ) {
    }

    #[AsEventListener(event: PaymentEvents::PAYMENT_COMPLETE)]
    public function onPaymentCapture(PaymentCompleteEvent $event): void
    {
        $this->notification->sendNotification(new PaymentReceivedNotification(['payment' => $event->getPayment()]));
    }
}
