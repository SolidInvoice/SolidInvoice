<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
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
    protected $notifications = array();

    /**
     * @param NotificationInterface[] $notifications
     * @param string                  $message
     * @param array                   $parameters
     */
    public function __construct(array $notifications = array(), $message = null, array $parameters = array())
    {
        parent::__construct($message, $parameters);

        foreach ($notifications as $notification) {
            $this->addNotifications($notification);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getNotifications()
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
