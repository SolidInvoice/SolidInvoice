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

use Namshi\Notificator\ManagerInterface;
use Namshi\Notificator\Notification\Handler\HandlerInterface;
use Namshi\Notificator\NotificationInterface;
use SolidInvoice\NotificationBundle\Notification\ChainedNotificationInterface;

class ChainedHandler implements HandlerInterface
{
    /**
     * @var ManagerInterface
     */
    private $manager;

    public function __construct(ManagerInterface $manager)
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
