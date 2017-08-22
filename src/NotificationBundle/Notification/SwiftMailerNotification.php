<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\NotificationBundle\Notification;

use Namshi\Notificator\Notification;
use Namshi\Notificator\Notification\Email\SwiftMailer\SwiftMailerNotificationInterface;
use Namshi\Notificator\NotificationInterface;

class SwiftMailerNotification extends Notification implements NotificationInterface, SwiftMailerNotificationInterface
{
}
