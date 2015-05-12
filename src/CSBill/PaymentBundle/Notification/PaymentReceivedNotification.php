<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Notification;

use CSBill\NotificationBundle\Notification\SwiftMailerNotification;
use CSBill\PaymentBundle\Event\PaymentCompleteEvent;
use CSBill\PaymentBundle\Mailer\PaymentMailer;
use CSBill\SettingsBundle\Manager\SettingsManager;
use Namshi\Notificator\Manager;

class PaymentReceivedNotification
{
    /**
     * @var SettingsManager
     */
    private $settings;

    /**
     * @var Manager
     */
    private $notification;

    /**
     * @var PaymentMailer
     */
    private $mailer;

    /**
     * @param SettingsManager $settings
     * @param Manager         $notification
     * @param PaymentMailer   $mailer
     */
    public function __construct(SettingsManager $settings, Manager $notification, PaymentMailer $mailer)
    {
        $this->settings = $settings;
        $this->notification = $notification;
        $this->mailer = $mailer;
    }

    /**
     * @param PaymentCompleteEvent $event
     */
    public function onPaymentCapture(PaymentCompleteEvent $event)
    {
        if ($this->settings->get('notifications.payment_made')) {
            $payment = $event->getPayment();

            $message = $this->mailer->createPaymentMail($payment);

            $notification = new SwiftMailerNotification($message);

            // trigger the notification
            $this->notification->trigger($notification);
        }
    }
}