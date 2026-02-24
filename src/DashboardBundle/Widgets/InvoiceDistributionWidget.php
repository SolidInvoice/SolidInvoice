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

namespace SolidInvoice\DashboardBundle\Widgets;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class InvoiceDistributionWidget implements WidgetInterface
{
    private ObjectManager $manager;

    /**
     * Status colors matching the design system.
     *
     * @var array<string, array{color: string, label: string}>
     */
    private const array STATUS_CONFIG = [
        InvoiceStatus::Paid->value => ['color' => 'rgb(16, 185, 129)', 'label' => 'Paid'],
        InvoiceStatus::Pending->value => ['color' => 'rgb(59, 130, 246)', 'label' => 'Pending'],
        InvoiceStatus::Overdue->value => ['color' => 'rgb(239, 68, 68)', 'label' => 'Overdue'],
        InvoiceStatus::Draft->value => ['color' => 'rgb(148, 163, 184)', 'label' => 'Draft'],
        InvoiceStatus::Cancelled->value => ['color' => 'rgb(100, 116, 139)', 'label' => 'Cancelled'],
    ];

    public function __construct(
        ManagerRegistry $registry,
        private ChartBuilderInterface $chartBuilder,
    ) {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        /** @var InvoiceRepository $invoiceRepository */
        $invoiceRepository = $this->manager->getRepository(Invoice::class);

        $statusCounts = $invoiceRepository->getCountByStatusAll();

        // Filter to only include statuses we want to display
        $relevantStatuses = [
            InvoiceStatus::Paid,
            InvoiceStatus::Pending,
            InvoiceStatus::Overdue,
            InvoiceStatus::Draft,
        ];

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($relevantStatuses as $status) {
            $count = $statusCounts[$status->value] ?? 0;
            if ($count > 0 || $status === InvoiceStatus::Pending) {
                $config = self::STATUS_CONFIG[$status->value];
                $labels[] = $config['label'];
                $data[] = $count;
                $colors[] = $config['color'];
            }
        }

        $hasData = array_sum($data) > 0;

        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderWidth' => 0,
                    'hoverOffset' => 8,
                ],
            ],
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '70%',
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 16,
                        'color' => '#475569',
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(30, 41, 59, 0.9)',
                    'titleColor' => '#fff',
                    'bodyColor' => '#fff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
        ]);

        return [
            'chart' => $chart,
            'hasData' => $hasData,
            'total' => array_sum($data),
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/invoice_distribution.html.twig';
    }
}
