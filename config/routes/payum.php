<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('@PayumBundle/Resources/config/routing/capture.xml');

    $routingConfigurator->import('@PayumBundle/Resources/config/routing/authorize.xml');

    $routingConfigurator->import('@PayumBundle/Resources/config/routing/notify.xml');
};
