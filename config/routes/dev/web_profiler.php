<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')
        ->prefix('/_wdt');

    $routingConfigurator->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')
        ->prefix('/_profiler');
};
