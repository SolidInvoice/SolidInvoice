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

namespace CSBill\NotificationBundle\Notification\Handler;

use CSBill\NotificationBundle\Notification\TwilioNotification;
use Namshi\Notificator\Notification\Handler\HandlerInterface;
use Namshi\Notificator\NotificationInterface;
use Twilio\Rest\Client;

class TwilioHandler implements HandlerInterface
{
    /**
     * @var Client
     */
    private $twilio;

    /**
     * @var string
     */
    private $twilioNumber;

    /**
     * @param Client $twilio
     * @param string $twilioNumber
     */
    public function __construct(Client $twilio, $twilioNumber)
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
            ->messages
            ->create(
                $notification->getRecipientNumber(),
                [
                    'from' => $this->twilioNumber,
                    'body' => $notification->getMessage(),
                ]
            );
    }
}
