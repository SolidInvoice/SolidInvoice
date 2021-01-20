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
     */
    public function __construct(array $notifications = [], string $message = null, array $parameters = [])
    {
        parent::__construct($message, $parameters);

        foreach ($notifications as $notification) {
            $this->addNotification($notification);
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
    public function addNotification(NotificationInterface $notification)
    {
        $this->notifications[] = $notification;
    }
}
