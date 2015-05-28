<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Notification\Handler;

use CSBill\NotificationBundle\Notification\TwilioNotification;
use Namshi\Notificator\Notification\Handler\HandlerInterface;
use Namshi\Notificator\NotificationInterface;
use Services_Twilio;

class TwilioHandler implements HandlerInterface
{
    /**
     * @var Services_Twilio
     */
    private $twilio;

    /**
     * @var string
     */
    private $twilioNumber;

    /**
     * @param Services_Twilio $twilio
     * @param string          $twilioNumber
     */
    public function __construct(Services_Twilio $twilio, $twilioNumber)
    {
        $this->twilio = $twilio;
        $this->twilioNumber = $twilioNumber;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldHandle(NotificationInterface $notification)
    {
        return $notification instanceof TwilioNotification;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(NotificationInterface $notification)
    {
        /* @var TwilioNotification $notification */

        $this->twilio
            ->account
            ->messages
            ->sendMessage(
                $this->twilioNumber,
                $notification->getRecipientNumber(),
                $notification->getMessage()
            );
    }
}
