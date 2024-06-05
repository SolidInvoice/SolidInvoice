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

use SolidInvoice\DashboardBundle\SolidInvoiceDashboardBundle;
use SolidInvoice\DashboardBundle\Widgets\RecentClientsWidget;
use SolidInvoice\DashboardBundle\Widgets\RecentInvoicesWidget;
use SolidInvoice\DashboardBundle\Widgets\RecentPaymentsWidget;
use SolidInvoice\DashboardBundle\Widgets\RecentQuotesWidget;
use SolidInvoice\DashboardBundle\Widgets\StatsWidget;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    $services
        ->load(SolidInvoiceDashboardBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->set(StatsWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 100,
            'location' => 'top',
        ]);

    $services
        ->set(RecentClientsWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 100,
            'location' => 'left_column',
        ]);

    $services
        ->set(RecentPaymentsWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 90,
            'location' => 'left_column',
        ]);

    $services
        ->set(RecentQuotesWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 100,
            'location' => 'right_column',
        ]);

    $services
        ->set(RecentInvoicesWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 90,
            'location' => 'right_column',
        ]);
};
