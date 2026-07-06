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

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('@PayumBundle/Resources/config/routing/capture.yaml');

    $routingConfigurator->import('@PayumBundle/Resources/config/routing/authorize.yaml');

    $routingConfigurator->import('@PayumBundle/Resources/config/routing/notify.yaml');
};
