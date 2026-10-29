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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use RuntimeException;
use SolidInvoice\DashboardBundle\Stats\DashboardStats;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\SettingsBundle\SystemConfig;
use function array_map;

/**
 * @see \SolidInvoice\DashboardBundle\Tests\Widgets\HeroStatsWidgetTest
 */
final readonly class HeroStatsWidget implements WidgetInterface
{
    public function __construct(
        private DashboardStats $stats,
        private PaymentRepository $paymentRepository,
        private SystemConfig $systemConfig,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws MathException
     */
    public function getData(): array
    {
        $amounts = static fn (array $summary): array => array_map(static fn (array $row): BigInteger => $row['amount'], $summary);

        return [
            'totalOutstanding' => $amounts($this->stats->outstanding()),
            'overdueCount' => $this->stats->invoiceCount(InvoiceStatus::Overdue),
            'overdueAmount' => $amounts($this->stats->overdue()),
            'paymentsThisMonth' => $amounts($this->stats->paidThisMonth()),
            'totalRevenue' => $this->paymentRepository->getTotalIncome(),
            'defaultCurrency' => $this->defaultCurrency(),
        ];
    }

    /**
     * The currency an empty stat should be denominated in.
     *
     * Every populated branch is denominated in the client's currency, which does
     * not exist when a stat has no rows behind it. The company's configured
     * currency is the only defensible basis in that case, so the widget states it
     * explicitly instead of leaving the template to guess.
     *
     * Null when no currency is configured yet (a fresh install): callers should
     * treat it as "unknown" rather than substituting an arbitrary currency.
     */
    private function defaultCurrency(): ?string
    {
        try {
            return $this->systemConfig->getCurrency()->getCode();
        } catch (RuntimeException) {
            return null;
        }
    }

    public function getTemplate(): string
    {
        return '@SolidInvoiceDashboard/Widget/hero_stats.html.twig';
    }
}
