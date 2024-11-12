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

namespace SolidInvoice\ClientBundle\Listener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\ObjectManager;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Model\Status;
use SolidInvoice\ClientBundle\Notification\ClientCreateNotification;
use SolidInvoice\NotificationBundle\Notification\NotificationManager;
use SolidInvoice\SettingsBundle\SystemConfig;

#[AsDoctrineListener(Events::prePersist)]
#[AsDoctrineListener(Events::postPersist)]
#[AsDoctrineListener(Events::postLoad)]
final class ClientListener
{
    public function __construct(
        private readonly NotificationManager $notification,
        private readonly SystemConfig $config,
    ) {
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function prePersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if (! $entity instanceof Client) {
            return;
        }

        if (! $entity->getId() && ! $entity->getStatus()) {
            $entity->setStatus(Status::STATUS_ACTIVE);

            if ($entity->getCurrencyCode() === null) {
                $entity->setCurrency($this->config->getCurrency());
            }
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postLoad(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if (! $entity instanceof Client) {
            return;
        }

        if (null === $entity->getCurrencyCode()) {
            $entity->setCurrency($this->config->getCurrency());
        } else {
            $entity->setCurrency(new Currency($entity->getCurrencyCode()));
        }
    }

    /**
     * @param LifecycleEventArgs<ObjectManager> $event
     */
    public function postPersist(LifecycleEventArgs $event): void
    {
        $entity = $event->getObject();

        if (! $entity instanceof Client) {
            return;
        }

        // client is created
        $this->notification->sendNotification(new ClientCreateNotification(['client' => $entity]));
    }
}
