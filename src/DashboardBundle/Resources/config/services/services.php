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

use SolidInvoice\DashboardBundle\Checklist\ChecklistItemInterface;
use SolidInvoice\DashboardBundle\Checklist\ChecklistManager;
use SolidInvoice\DashboardBundle\SolidInvoiceDashboardBundle;
use SolidInvoice\DashboardBundle\Widgets\AttentionRequiredWidget;
use SolidInvoice\DashboardBundle\Widgets\HeroStatsWidget;
use SolidInvoice\DashboardBundle\Widgets\InvoiceDistributionWidget;
use SolidInvoice\DashboardBundle\Widgets\OnboardingChecklistWidget;
use SolidInvoice\DashboardBundle\Widgets\QuickActionsWidget;
use SolidInvoice\DashboardBundle\Widgets\RecentActivityWidget;
use SolidInvoice\DashboardBundle\Widgets\RevenueChartWidget;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services
        ->defaults()
        ->autoconfigure()
        ->autowire()
        ->private()
    ;

    // Auto-tag checklist items (must come before load())
    $services
        ->instanceof(ChecklistItemInterface::class)
        ->tag('dashboard.checklist_item');

    $services
        ->load(SolidInvoiceDashboardBundle::NAMESPACE . '\\', dirname(__DIR__, 3))
        ->exclude(dirname(__DIR__, 3) . '/{DependencyInjection,Entity,Resources,Tests}');

    $services
        ->load(SolidInvoiceDashboardBundle::NAMESPACE . '\\Action\\', dirname(__DIR__, 3) . '/Action')
        ->tag('controller.service_arguments');

    // Configure ChecklistManager with tagged items
    $services
        ->set(ChecklistManager::class)
        ->arg('$items', tagged_iterator('dashboard.checklist_item'));

    // Top row - Onboarding Checklist (highest priority)
    $services
        ->set(OnboardingChecklistWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 300,
            'location' => 'top',
        ]);

    // Top row - Hero Stats
    $services
        ->set(HeroStatsWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 200,
            'location' => 'top',
        ]);

    // Left column - Attention first, then reflection (charts and activity).
    // AttentionRequired leads the wide column so the actionable widget is reached
    // before the 12-month trend chart, both on desktop and in the stacked
    // mobile order (top row, then left column, then right column).
    $services
        ->set(AttentionRequiredWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 120,
            'location' => 'left_column',
        ]);

    $services
        ->set(RevenueChartWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 100,
            'location' => 'left_column',
        ]);

    // Right column - Actions, then Activity, then Distribution.
    //
    // Activity sits here rather than under the chart: the left column had grown
    // long enough that the feed was being scrolled past and missed. Distribution
    // stays last, and stays least actionable.
    $services
        ->set(QuickActionsWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 110,
            'location' => 'right_column',
        ]);

    $services
        ->set(RecentActivityWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 50,
            'location' => 'right_column',
        ]);

    $services
        ->set(InvoiceDistributionWidget::class)
        ->tag('dashboard.widget', [
            'priority' => 10,
            'location' => 'right_column',
        ]);
};
