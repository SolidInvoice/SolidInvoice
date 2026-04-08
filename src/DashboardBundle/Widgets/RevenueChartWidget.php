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

use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\SettingsBundle\SystemConfig;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class RevenueChartWidget implements WidgetInterface
{
    private ObjectManager $manager;

    public function __construct(
        ManagerRegistry $registry,
        private ChartBuilderInterface $chartBuilder,
        private SystemConfig $systemConfig,
    ) {
        $this->manager = $registry->getManager();
    }

    /**
     * @return array<string, mixed>
     * @throws DateMalformedStringException
     */
    public function getData(): array
    {
        /** @var PaymentRepository $paymentRepository */
        $paymentRepository = $this->manager->getRepository(Payment::class);

        $revenueData = $paymentRepository->getRevenueByMonthGrouped(12);

        // Generate labels for the last 12 months
        $labels = [];
        $now = new DateTimeImmutable();

        for ($i = 11; $i >= 0; $i--) {
            $date = $now->modify(sprintf('-%d months', $i));
            $labels[] = $date->format('M Y');
        }

        // Get all currencies from the data
        $currencies = [];
        foreach ($revenueData as $monthData) {
            foreach (array_keys($monthData) as $currency) {
                if (! in_array($currency, $currencies, true)) {
                    $currencies[] = $currency;
                }
            }
        }

        // If no data, default to a single currency placeholder
        if ([] === $currencies) {
            $currencies = [$this->systemConfig->getCurrency()->getCode()];
        }

        // Build datasets for each currency
        $datasets = [];
        $colors = [
            ['border' => 'rgb(46, 150, 58)', 'background' => 'rgba(46, 150, 58, 0.1)'],
            ['border' => 'rgb(59, 130, 246)', 'background' => 'rgba(59, 130, 246, 0.1)'],
            ['border' => 'rgb(245, 158, 11)', 'background' => 'rgba(245, 158, 11, 0.1)'],
            ['border' => 'rgb(139, 92, 246)', 'background' => 'rgba(139, 92, 246, 0.1)'],
        ];

        foreach ($currencies as $index => $currency) {
            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $date = $now->modify(sprintf('-%d months', $i));
                $monthKey = $date->format('Y-m');
                $data[] = isset($revenueData[$monthKey][$currency])
                    ? $revenueData[$monthKey][$currency]->dividedBy(BigNumber::of(100), RoundingMode::HalfEven)->toFloat() // Convert cents to currency units
                    : 0;
            }

            $colorIndex = $index % count($colors);
            $datasets[] = [
                'label' => $currency,
                'data' => $data,
                'borderColor' => $colors[$colorIndex]['border'],
                'backgroundColor' => $colors[$colorIndex]['background'],
                'fill' => true,
                'tension' => 0.4,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
            ];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => $datasets,
        ]);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => [
                'legend' => [
                    'display' => count($currencies) > 1,
                    'position' => 'top',
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
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'color' => '#64748b',
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'color' => '#64748b',
                    ],
                ],
            ],
        ]);

        return [
            'chart' => $chart,
            'hasData' => ! empty($revenueData),
        ];
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/revenue_chart.html.twig';
    }
}
