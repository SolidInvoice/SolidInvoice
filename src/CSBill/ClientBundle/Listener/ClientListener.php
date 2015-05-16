<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Listener;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Notification\ClientCreateNotification;
use CSBill\ClientBundle\Notification\ClientUpdateNotification;
use CSBill\NotificationBundle\Notification\NotificationManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ClientListener
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
     * @param LifecycleEventArgs $event
     */
    public function postPersist(LifecycleEventArgs $event)
    {
        $entity = $event->getEntity();

        if (!$entity instanceof Client) {
            return;
        }

        if (null === $entity->getId()) {
            // client is created
            $notification = new ClientCreateNotification(array('client' => $entity));
            $event = 'client_create';
        } else {
            // client is updated
            $notification = new ClientUpdateNotification(array('client' => $entity));
            $event = 'client_update';
        }

        $this->notification->sendNotification($event, $notification);
    }
}