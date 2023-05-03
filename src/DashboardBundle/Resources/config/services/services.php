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

use SolidInvoice\DashboardBundle\Widgets\RecentClientsWidget;
use SolidInvoice\DashboardBundle\Widgets\RecentInvoicesWidget;
use SolidInvoice\DashboardBundle\Widgets\RecentPaymentsWidget;
use SolidInvoice\DashboardBundle\Widgets\RecentQuotesWidget;
use SolidInvoice\DashboardBundle\Widgets\StatsWidget;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $services->load('SolidInvoice\\DashboardBundle\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Resources,Tests}');

    $services->set(StatsWidget::class)
        ->args([service('doctrine')])
        ->tag('dashboard.widget', [
            'priority' => 100,
            'location' => 'top',
        ]);

    $services->set(RecentClientsWidget::class)
        ->args([service('doctrine')])
        ->tag('dashboard.widget', [
            'priority' => 100,
            'location' => 'left_column',
        ]);

    $services->set(RecentPaymentsWidget::class)
        ->args([service('doctrine')])
        ->tag('dashboard.widget', [
            'priority' => 90,
            'location' => 'left_column',
        ]);

    $services->set(RecentQuotesWidget::class)
        ->args([service('doctrine')])
        ->tag('dashboard.widget', [
            'priority' => 100,
            'location' => 'right_column',
        ]);

    $services->set(RecentInvoicesWidget::class)
        ->args([service('doctrine')])
        ->tag('dashboard.widget', [
            'priority' => 90,
            'location' => 'right_column',
        ]);
};
