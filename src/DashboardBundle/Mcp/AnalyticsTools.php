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
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\ClientBundle\Repository\ClientRepository;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\McpBundle\Mcp\Attribute\McpScopeRequired;
use SolidInvoice\McpBundle\Mcp\McpScopeGuard;
use SolidInvoice\McpBundle\Mcp\Tool\UlidParser;
use SolidInvoice\McpBundle\Security\McpScope;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

final class AnalyticsTools
{
    public function __construct(
        private readonly InvoiceRepository $invoiceRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly ClientRepository $clientRepository,
        private readonly McpScopeGuard $scopeGuard,
    ) {
    }

    /**
     * Dashboard hero stats: outstanding, overdue, paid, and counts — grouped per currency.
     *
     * @return array{
     *     outstanding: array<string, string>,
     *     overdue: array<string, string>,
     *     counts_by_status: array<string, int>,
     *     total_clients: int,
     *     total_invoices: int,
     * }
     */
    #[McpTool(name: 'get_dashboard_stats', description: 'Aggregate dashboard stats: outstanding and overdue totals per currency, invoice counts by status, client count.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getDashboardStats(): array
    {
        $this->scopeGuard->require(McpScope::Read);

        $outstanding = [];

        foreach ($this->invoiceRepository->getTotalOutstandingByCurrency() as $currency => $total) {
            $outstanding[(string) $currency] = $total->__toString();
        }

        $overdue = [];

        foreach ($this->invoiceRepository->getOverdueAmountByCurrency() as $currency => $total) {
            $overdue[(string) $currency] = $total->__toString();
        }

        $totalInvoices = (int) $this->invoiceRepository->createQueryBuilder('i')
            ->select('COUNT(i)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalClients = (int) $this->clientRepository->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'outstanding' => $outstanding,
            'overdue' => $overdue,
            'counts_by_status' => $this->invoiceRepository->getCountByStatusAll(),
            'total_invoices' => $totalInvoices,
            'total_clients' => $totalClients,
        ];
    }

    /**
     * Total outstanding (pending + overdue) per currency, optionally for a single client.
     *
     * @param string|null $client_id Optional client ULID
     *
     * @return array<string, string>
     */
    #[McpTool(name: 'get_total_outstanding', description: 'Total outstanding invoiced amounts per currency, optionally filtered by client.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getTotalOutstanding(?string $client_id = null): array
    {
        $this->scopeGuard->require(McpScope::Read);

        if ($client_id === null) {
            $result = [];

            foreach ($this->invoiceRepository->getTotalOutstandingByCurrency() as $currency => $total) {
                $result[(string) $currency] = $total->__toString();
            }

            return $result;
        }

        $client = $this->resolveClient($client_id);

        $sum = $this->invoiceRepository->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.balance), 0)')
            ->andWhere('i.client = :client AND i.status IN (:statuses)')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('statuses', [InvoiceStatus::Pending, InvoiceStatus::Overdue])
            ->getQuery()
            ->getSingleScalarResult();

        return [$this->requireClientCurrency($client) => (string) ($sum ?? 0)];
    }

    /**
     * Total overdue amount per currency, optionally for a single client.
     *
     * @param string|null $client_id Optional client ULID
     *
     * @return array<string, string>
     */
    #[McpTool(name: 'get_total_overdue', description: 'Total overdue invoiced amounts per currency, optionally filtered by client.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getTotalOverdue(?string $client_id = null): array
    {
        $this->scopeGuard->require(McpScope::Read);

        if ($client_id === null) {
            $result = [];

            foreach ($this->invoiceRepository->getOverdueAmountByCurrency() as $currency => $total) {
                $result[(string) $currency] = $total->__toString();
            }

            return $result;
        }

        $client = $this->resolveClient($client_id);

        $sum = $this->invoiceRepository->createQueryBuilder('i')
            ->select('COALESCE(SUM(i.balance), 0)')
            ->andWhere('i.client = :client AND i.status = :status')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('status', InvoiceStatus::Overdue)
            ->getQuery()
            ->getSingleScalarResult();

        return [$this->requireClientCurrency($client) => (string) ($sum ?? 0)];
    }

    /**
     * Count of invoices grouped by status (across all currencies).
     *
     * @return array<string, int>
     */
    #[McpTool(name: 'get_invoice_status_distribution', description: 'Count of invoices grouped by status.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getInvoiceStatusDistribution(): array
    {
        $this->scopeGuard->require(McpScope::Read);

        return $this->invoiceRepository->getCountByStatusAll();
    }

    /**
     * Revenue (captured payments) grouped by day, week, or month between two dates.
     *
     * @param string $start_date ISO-8601 date (inclusive)
     * @param string $end_date   ISO-8601 date (inclusive)
     * @param string $group_by   "day" (default), "week", or "month"
     *
     * @return array<string, array<string, string>>
     */
    #[McpTool(name: 'get_revenue_by_period', description: 'Revenue from captured payments between two dates, grouped by day, week, or month.')]
    #[McpScopeRequired(McpScope::Read)]
    public function getRevenueByPeriod(string $start_date, string $end_date, string $group_by = 'day'): array
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
        $qb = $this->paymentRepository->createQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->andWhere('p.completed >= :start AND p.completed < :end')
            ->setParameter('status', PaymentStatus::Captured->value)
            ->setParameter('start', $start)
            ->setParameter('end', $end->modify('+1 day'));

        $rows = $qb->select(
            'p.currencyCode AS currency',
            'p.totalAmount AS amount',
            'p.completed AS completed',
        )->getQuery()->getArrayResult();

        /** @var array<string, array<string, int>> $totals */
        $totals = [];

        foreach ($rows as $row) {
            $completed = $row['completed'];

            if (! $completed instanceof \DateTimeInterface) {
                continue;
            }

            $bucket = match ($groupBy) {
                'day' => $completed->format('Y-m-d'),
                'week' => $completed->format('o-\WW'),
                'month' => $completed->format('Y-m'),
            };

            $currency = (string) ($row['currency'] ?? 'USD');
            $amount = (int) ($row['amount'] ?? 0);

            if (! isset($totals[$bucket])) {
                $totals[$bucket] = [];
            }

            $totals[$bucket][$currency] = ($totals[$bucket][$currency] ?? 0) + $amount;
        }

        ksort($totals);

        $result = [];

        foreach ($totals as $bucket => $byCurrency) {
            $result[$bucket] = array_map(static fn (int $v): string => (string) $v, $byCurrency);
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

    private function requireClientCurrency(Client $client): string
    {
        $currency = $client->getCurrencyCode();

        if ($currency === null || $currency === '') {
            throw new ToolCallException(sprintf(
                'Client %s has no currency configured.',
                $client->getId()?->toRfc4122() ?? '',
            ));
        }

        return $currency;
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        try {
            return new DateTimeImmutable($value);
        } catch (\Exception) {
            throw new ToolCallException(sprintf('Invalid date "%s". Use ISO-8601 format (YYYY-MM-DD).', $value));
        }
    }
}
