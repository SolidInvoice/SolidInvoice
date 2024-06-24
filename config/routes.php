<?php

declare(strict_types=1);

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routingConfigurator): void {
    $routingConfigurator->import('@SolidInvoiceDashboardBundle/Resources/config/routing.php');

    $routingConfigurator->import('@SolidInvoiceSettingsBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@SolidInvoiceCoreBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@SolidInvoiceInstallBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@SolidInvoiceClientBundle/Resources/config/routing.php')
        ->prefix('/clients');

    $routingConfigurator->import('@SolidInvoiceQuoteBundle/Resources/config/routing.php')
        ->prefix('/quotes');

    $routingConfigurator->import('@SolidInvoiceInvoiceBundle/Resources/config/routing.php')
        ->prefix('/invoices');

    $routingConfigurator->import('@SolidInvoicePaymentBundle/Resources/config/routing.php')
        ->prefix('/payments');

    $routingConfigurator->import('@SolidInvoiceTaxBundle/Resources/config/routing.php')
        ->prefix('/tax');

    $routingConfigurator->import('@SolidInvoiceUserBundle/Resources/config/routing.php')
        ->prefix('/');

    $routingConfigurator->import('@SolidInvoiceNotificationBundle/Resources/config/routing.php')
        ->prefix('/notifications');
};
