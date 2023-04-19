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

namespace SolidInvoice\NotificationBundle\Notification\Handler;

use Namshi\Notificator\Notification\Handler\HandlerInterface;
use Namshi\Notificator\NotificationInterface;
use SolidInvoice\NotificationBundle\Notification\TwilioNotification;
use SolidInvoice\SettingsBundle\SystemConfig;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class TwilioHandler implements HandlerInterface
{
    private Client $twilio;

    private SystemConfig $config;

    public function __construct(Client $twilio, SystemConfig $config)
    {
        $this->twilio = $twilio;
        $this->config = $config;
    }

    public function shouldHandle(NotificationInterface $notification): bool
    {
        return $notification instanceof TwilioNotification;
    }

    /**
     * @throws TwilioException
     */
    public function handle(NotificationInterface $notification): void
    {
        $number = $this->config->get('sms/twilio/number');

        if (! empty($number)) {
            /** @var TwilioNotification $notification */
            $this->twilio->messages
                ->create(
                    (string) $notification->getRecipientNumber(),
                    [
                        'from' => $number,
                        'body' => $notification->getMessage(),
                    ]
                );
        }
    }
}
