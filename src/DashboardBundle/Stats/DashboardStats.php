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

namespace SolidInvoice\DashboardBundle\Stats;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;
use function sprintf;

/**
 * The figures behind the dashboard, for the dashboard widgets, the MCP analytics tools and the
 * dashboard API alike, so they never disagree. Amounts are in the currency's minor unit, per the
 * client currency they were invoiced or paid in; nothing is converted or summed across currencies.
 */
final readonly class DashboardStats
{
    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private PaymentRepository $paymentRepository,
        private QuoteRepository $quoteRepository,
        private ClockInterface $clock,
    ) {
    }

    /**
     * Pending and overdue invoices: what clients still owe.
     *
     * @return array<string, array{amount: BigInteger, count: int}>
     * @throws MathException
     */
    public function outstanding(): array
    {
        return $this->invoiceRepository->getBalanceSummaryByCurrency(InvoiceStatus::Pending, InvoiceStatus::Overdue);
    }

    /**
     * @return array<string, array{amount: BigInteger, count: int}>
     * @throws MathException
     */
    public function overdue(): array
    {
        return $this->invoiceRepository->getBalanceSummaryByCurrency(InvoiceStatus::Overdue);
    }

    /**
     * Captured payments by their payment date, in [$from, $to).
     *
     * @return array<string, array{amount: BigInteger, count: int}>
     * @throws MathException
     */
    public function paid(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $rows = $this->paymentRepository->createQueryBuilder('p')
            ->select('SUM(p.totalAmount) AS total', 'COUNT(p.id) AS payments', 'p.currencyCode')
            ->where('p.status = :status')
            ->andWhere('p.completed >= :from AND p.completed < :to')
            ->setParameter('status', PaymentStatus::Captured->value)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('p.currencyCode')
            ->getQuery()
            ->getArrayResult();

        $paid = [];

        foreach ($rows as $row) {
            if ($row['currencyCode'] !== null && $row['currencyCode'] !== '' && $row['total'] !== null) {
                $paid[$row['currencyCode']] = ['amount' => BigInteger::of($row['total']), 'count' => (int) $row['payments']];
            }
        }

        return $paid;
    }

    /**
     * @return array<string, array{amount: BigInteger, count: int}>
     * @throws MathException
     */
    public function paidThisMonth(): array
    {
        $start = $this->startOfMonth();

        return $this->paid($start, $start->modify('+1 month'));
    }

    /**
     * @return array<string, array{amount: BigInteger, count: int}>
     * @throws MathException
     */
    public function paidLastMonth(): array
    {
        $start = $this->startOfMonth();

        return $this->paid($start->modify('-1 month'), $start);
    }

    /**
     * Captured payments per month (`Y-m`) and currency, oldest first, for the last $months months
     * including this one.
     *
     * @return list<array{month: string, currency: string, amount: BigInteger}>
     * @throws MathException
     */
    public function revenueByMonth(int $months): array
    {
        $start = $this->startOfMonth()->modify(sprintf('-%d months', $months - 1));
        $revenue = [];

        for ($month = $start, $i = 0; $i < $months; $month = $month->modify('+1 month'), ++$i) {
            foreach ($this->paid($month, $month->modify('+1 month')) as $currency => $paid) {
                $revenue[] = ['month' => $month->format('Y-m'), 'currency' => $currency, 'amount' => $paid['amount']];
            }
        }

        return $revenue;
    }

    public function invoiceCount(InvoiceStatus $status): int
    {
        return $this->invoiceRepository->getCountByStatus($status);
    }

    public function quoteCount(QuoteStatus $status): int
    {
        return $this->quoteRepository->getTotalQuotes($status);
    }

    /**
     * There is no acceptance date, so this counts accepted quotes last changed this month.
     */
    public function quotesAcceptedThisMonth(): int
    {
        return (int) $this->quoteRepository->createQueryBuilder('q')
            ->select('COUNT(q.id)')
            ->where('q.status = :status')
            ->andWhere('q.updated >= :start')
            ->setParameter('status', QuoteStatus::Accepted)
            ->setParameter('start', $this->startOfMonth())
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Oldest due first.
     *
     * @return list<Invoice>
     */
    public function overdueInvoices(int $limit): array
    {
        return $this->invoiceRepository->getOverdueInvoices($limit);
    }

    /**
     * Newest first.
     *
     * @return list<Invoice>
     */
    public function draftInvoices(int $limit): array
    {
        return $this->invoiceRepository->getDraftInvoices($limit);
    }

    /**
     * Newest first.
     *
     * @return list<Quote>
     */
    public function pendingQuotes(int $limit): array
    {
        return $this->quoteRepository->getPendingQuotes($limit);
    }

    /**
     * Pending quotes that expire today or within $days days, soonest first.
     *
     * @return list<Quote>
     */
    public function expiringQuotes(int $days, int $limit): array
    {
        $today = $this->today();

        return $this->quoteRepository->createQueryBuilder('q')
            ->innerJoin('q.client', 'c')
            ->addSelect('c')
            ->where('q.status = :status')
            ->andWhere('q.due >= :today AND q.due <= :until')
            ->setParameter('status', QuoteStatus::Pending)
            ->setParameter('today', $today)
            ->setParameter('until', $today->modify(sprintf('+%d days', $days)))
            ->orderBy('q.due', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function today(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($this->clock->now())->setTime(0, 0);
    }

    private function startOfMonth(): DateTimeImmutable
    {
        return $this->today()->modify('first day of this month');
    }
}
