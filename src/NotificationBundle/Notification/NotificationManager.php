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

namespace SolidInvoice\NotificationBundle\Notification;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Namshi\Notificator\ManagerInterface;
use SolidInvoice\SettingsBundle\Exception\InvalidSettingException;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\UserBundle\Entity\User;

class NotificationManager
{
    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var ManagerInterface
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

    public function __construct(
        Factory $factory,
        SystemConfig $settings,
        ManagerInterface $notification,
        ManagerRegistry $doctrine
    ) {
        $this->factory = $factory;
        $this->notification = $notification;
        $this->settings = $settings;
        $this->entityManager = $doctrine->getManager();
    }

    /**
     * @throws InvalidSettingException
     */
    public function sendNotification(string $event, NotificationMessageInterface $message)
    {
        /** @var EntityRepository $repository */
        $repository = $this->entityManager->getRepository(User::class);

        $message->setUsers($repository->findAll());

        $notification = new ChainedNotification();

        // @TODO: Settings should automatically be decoded
        $settings = json_decode($this->settings->get(sprintf('notification/%s', $event)), true, 512, JSON_THROW_ON_ERROR);

        if ((bool) $settings['email']) {
            $notification->addNotification($this->factory->createEmailNotification($message));
        }

        if ((bool) $settings['sms']) {
            foreach ($message->getUsers() as $user) {
                if (null === $user->getMobile()) {
                    continue;
                }

                $notification->addNotification(
                    $this->factory->createSmsNotification($user->getMobile(), $message)
                );
            }
        }

        $this->notification->trigger($notification);
    }
}
