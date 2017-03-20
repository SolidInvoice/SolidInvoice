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

namespace CSBill\NotificationBundle\Notification;

use Namshi\Notificator\Notification;
use Namshi\Notificator\NotificationInterface;

class ChainedNotification extends Notification implements ChainedNotificationInterface
{
    /**
     * @var NotificationInterface[]
     */
    protected $notifications = [];

    /**
     * @param NotificationInterface[] $notifications
     * @param string                  $message
     * @param array                   $parameters
     */
    public function __construct(array $notifications = [], string $message = null, array $parameters = [])
    {
        parent::__construct($message, $parameters);

        foreach ($notifications as $notification) {
            $this->addNotifications($notification);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * {@inheritdoc}
     */
    public function addNotifications(NotificationInterface $notification)
    {
        $this->notifications[] = $notification;
    }
}
