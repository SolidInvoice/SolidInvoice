<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Notification;

use CSBill\SettingsBundle\SystemConfig;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Namshi\Notificator\Manager;

class NotificationManager
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Manager
     */
    private $notification;

    /**
     * @var SystemConfig
     */
    private $settings;

    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * @param Factory         $factory
     * @param SystemConfig    $settings
     * @param Manager         $notification
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Factory $factory,
        SystemConfig $settings,
        Manager $notification,
        ManagerRegistry $doctrine
    ) {
        $this->factory = $factory;
        $this->notification = $notification;
        $this->settings = $settings;
        $this->entityManager = $doctrine->getManager();
    }

    /**
     * @param string                       $event
     * @param NotificationMessageInterface $message
     */
    public function sendNotification(string $event, NotificationMessageInterface $message)
    {
        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository('CSBillUserBundle:User');

        $message->setUsers($repository->findAll());

        $notification = new ChainedNotification();

        if ($this->settings->get(sprintf('notification/%s/email', $event))) {
            $notification->addNotifications($this->factory->createEmailNotification($message));
        }

        if ($this->settings->get(sprintf('notification/%s/hipchat', $event))) {
            $notification->addNotifications($this->factory->createHipchatNotification($message));
        }

        if ($this->settings->get(sprintf('notification/%s/sms', $event))) {
            foreach ($message->getUsers() as $user) {
                if (null === $user->getMobile()) {
                    continue;
                }

                $notification->addNotifications(
                    $this->factory->createSmsNotification($user->getMobile(), $message)
                );
            }
        }

        $this->notification->trigger($notification);
    }
}
