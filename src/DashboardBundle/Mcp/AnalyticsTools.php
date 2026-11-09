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

namespace SolidInvoice\DashboardBundle\Mcp;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Output\DashboardStatsOutput;
use SolidInvoice\McpBundle\Mcp\Output\McpOutput;
use SolidInvoice\McpBundle\Mcp\Output\RevenueByPeriodOutput;
use SolidInvoice\McpBundle\Mcp\Output\RevenuePeriodOutput;
use SolidInvoice\McpBundle\Mcp\Output\StatusCountOutput;
use SolidInvoice\McpBundle\Mcp\Output\StatusDistributionOutput;
use SolidInvoice\McpBundle\Mcp\Output\TotalsOutput;
use SolidInvoice\McpBundle\Mcp\Tool\CurrencyTotals;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;

final readonly class AnalyticsTools
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private ClientRepository $clientRepository,
        private CurrencyTotals $currencyTotals,
        private McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Dashboard hero stats: outstanding and overdue per currency, and counts.
     */
    #[McpTool(name: 'get_dashboard_stats', title: 'Get dashboard stats', description: 'Aggregate dashboard stats: outstanding and overdue totals per currency, invoice counts by status, client count.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(DashboardStatsOutput::class)]
    public function getDashboardStats(): DashboardStatsOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $totalInvoices = (int) $this->invoiceRepository->createQueryBuilder('i')
            ->select('COUNT(i)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalClients = (int) $this->clientRepository->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->getQuery()
            ->getSingleScalarResult();

        return new DashboardStatsOutput(
            outstanding: $this->currencyTotals->invoices('balance', [InvoiceStatus::Pending, InvoiceStatus::Overdue]),
            overdue: $this->currencyTotals->invoices('balance', [InvoiceStatus::Overdue]),
            counts_by_status: $this->statusCounts($this->invoiceRepository->getCountByStatusAll()),
            total_invoices: $totalInvoices,
            total_clients: $totalClients,
        );
    }

    /**
     * Total outstanding (pending + overdue) per currency, optionally for a single client.
     *
     * @param string|null $client_id Optional client ULID
     */
    #[McpTool(name: 'get_total_outstanding', title: 'Get total outstanding', description: 'Total outstanding invoiced amounts per currency, optionally filtered by client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(TotalsOutput::class)]
    public function getTotalOutstanding(?string $client_id = null): TotalsOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        return new TotalsOutput($this->currencyTotals->invoices(
            'balance',
            [InvoiceStatus::Pending, InvoiceStatus::Overdue],
            $client_id === null ? null : $this->resolveClient($client_id),
        ));
    }

    /**
     * Total overdue amount per currency, optionally for a single client.
     *
     * @param string|null $client_id Optional client ULID
     */
    #[McpTool(name: 'get_total_overdue', title: 'Get total overdue', description: 'Total overdue invoiced amounts per currency, optionally filtered by client.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(TotalsOutput::class)]
    public function getTotalOverdue(?string $client_id = null): TotalsOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        return new TotalsOutput($this->currencyTotals->invoices(
            'balance',
            [InvoiceStatus::Overdue],
            $client_id === null ? null : $this->resolveClient($client_id),
        ));
    }

    /**
     * Count of invoices grouped by status (across all currencies).
     */
    #[McpTool(name: 'get_invoice_status_distribution', title: 'Get invoice status distribution', description: 'Count of invoices grouped by status.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(StatusDistributionOutput::class)]
    public function getInvoiceStatusDistribution(): StatusDistributionOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        return new StatusDistributionOutput($this->statusCounts($this->invoiceRepository->getCountByStatusAll()));
    }

    /**
     * Revenue (captured payments) grouped by day, week, or month between two dates.
     *
     * @param string $start_date ISO-8601 date (inclusive)
     * @param string $end_date   ISO-8601 date (inclusive)
     * @param string $group_by   "day" (default), "week", or "month"
     */
    #[McpTool(name: 'get_revenue_by_period', title: 'Get revenue by period', description: 'Revenue from captured payments between two dates, grouped by day, week, or month.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    #[McpScopeRequired(McpScope::Read)]
    #[McpOutput(RevenueByPeriodOutput::class)]
    public function getRevenueByPeriod(string $start_date, string $end_date, string $group_by = 'day'): RevenueByPeriodOutput
    {
        $this->scopeGuard->require(McpScope::Read);

        $start = $this->parseDate($start_date);
        $end = $this->parseDate($end_date);

        if ($start > $end) {
            throw new ToolCallException('start_date must be before or equal to end_date.');
        }

        $groupBy = match (strtolower($group_by)) {
            'day' => 'day',
            'week' => 'week',
            'month' => 'month',
            default => throw new ToolCallException('group_by must be one of: day, week, month.'),
        };

        // Half-open range: >= start AND < end+1day avoids the off-by-one
        // you'd get with BETWEEN, which is inclusive on both bounds and would
        // count payments completed at 00:00:00 of the day after end_date.
        $rows = $this->paymentRepository->createQueryBuilder('p')
            ->select('p.currencyCode AS currency', 'p.totalAmount AS amount', 'p.completed AS completed')
            ->andWhere('p.status = :status')
            ->andWhere('p.completed >= :start AND p.completed < :end')
            ->setParameter('status', PaymentStatus::Captured->value)
            ->setParameter('start', $start)
            ->setParameter('end', $end->modify('+1 day'))
            ->getQuery()
            ->getArrayResult();

        /** @var array<string, list<array{currency: ?string, amount: int|string|null}>> $buckets */
        $buckets = [];

        foreach ($rows as $row) {
            $completed = $row['completed'];

            if (! $completed instanceof DateTimeInterface) {
                continue;
            }

            $bucket = match ($groupBy) {
                'day' => $completed->format('Y-m-d'),
                'week' => $completed->format('o-\WW'),
                'month' => $completed->format('Y-m'),
            };

            $buckets[$bucket][] = ['currency' => $row['currency'], 'amount' => $row['amount']];
        }

        ksort($buckets);

        $periods = [];

        foreach ($buckets as $period => $bucketRows) {
            $periods[] = new RevenuePeriodOutput((string) $period, $this->currencyTotals->amounts($bucketRows));
        }

        return new RevenueByPeriodOutput($groupBy, $periods);
    }

    /**
     * @param array<string, int> $counts
     *
     * @return list<StatusCountOutput>
     */
    private function statusCounts(array $counts): array
    {
        $result = [];

        foreach ($counts as $status => $count) {
            // A row without a (known) status has no bucket to count in.
            $invoiceStatus = InvoiceStatus::tryFrom((string) $status);

            if ($invoiceStatus instanceof InvoiceStatus) {
                $result[] = new StatusCountOutput($invoiceStatus, $count);
            }
        }

        return $result;
    }

    private function resolveClient(string $clientId): Client
    {
        $client = $this->clientRepository->find(UlidParser::parse($clientId, 'client_id'));

        if (! $client instanceof Client) {
            throw new ToolCallException(sprintf('Client %s not found.', $clientId));
        }

        return $client;
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            throw new ToolCallException(sprintf('Invalid date "%s". Use ISO-8601 format (YYYY-MM-DD).', $value));
        }
    }
}
