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

use CSBill\NotificationBundle\Notification\ChainedNotificationInterface;
use Namshi\Notificator\Manager;
use Namshi\Notificator\Notification\Handler\HandlerInterface;
use Namshi\Notificator\NotificationInterface;

class ChainedHandler implements HandlerInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldHandle(NotificationInterface $notification)
    {
        return $notification instanceof ChainedNotificationInterface;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(NotificationInterface $notification)
    {
        /* @var ChainedNotificationInterface $notification */
        foreach ($notification->getNotifications() as $notify) {
            $this->manager->trigger($notify);
        }
    }
}
