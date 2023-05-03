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

use Namshi\Notificator\Manager;
use Namshi\Notificator\ManagerInterface;
use Namshi\Notificator\Notification\Handler\HandlerInterface;
use SolidInvoice\NotificationBundle\Notification\Factory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Twilio\Rest\Client;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $services->load('SolidInvoice\\NotificationBundle\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Resources,Tests}');

    $services->instanceof(HandlerInterface::class)
        ->tag('notification.handler');

    $services->set(Manager::class);

    $services->alias(ManagerInterface::class, Manager::class);

    $services->set(Client::class)
        ->factory([Factory::class, 'createTwilioClient']);
};
