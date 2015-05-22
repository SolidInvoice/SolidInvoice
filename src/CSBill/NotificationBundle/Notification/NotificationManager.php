<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\NotificationBundle\Notification;

use CSBill\SettingsBundle\Manager\SettingsManager;
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
     * @var SettingsManager
     */
    private $settings;

    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * @param Factory         $factory
     * @param SettingsManager $settings
     * @param Manager         $notification
     * @param ManagerRegistry $doctrine
     */
    public function __construct(
        Factory $factory,
        SettingsManager $settings,
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
    public function sendNotification($event, NotificationMessageInterface $message)
    {
        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository('CSBillUserBundle:User');

        $message->setUsers($repository->findAll());

        $notification = new ChainedNotification();

        $settings = $this->settings->get(sprintf('notification.%s', $event));

        if ($settings['email']) {
            $notification->addNotifications($this->factory->createEmailNotification($message));
        }

        if ($settings['hipchat']) {
            $notification->addNotifications($this->factory->createHipchatNotification($message));
        }

        if ($settings['sms']) {
            foreach ($message->getUsers() as $user) {
                $notification->addNotifications(
                    $this->factory->createSmsNotification($user->getMobile(), $message)
                );
            }
        }

        $this->notification->trigger($notification);
    }
}