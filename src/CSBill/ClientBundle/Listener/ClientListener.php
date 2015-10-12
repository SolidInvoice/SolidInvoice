<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Listener;

use CSBill\ClientBundle\Entity\Client;
use CSBill\ClientBundle\Notification\ClientCreateNotification;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ClientListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        // client is created
        $notification = new ClientCreateNotification(array('client' => $entity));

        $this->container
            ->get('notification.manager')
            ->sendNotification('client_create', $notification);
    }
}
