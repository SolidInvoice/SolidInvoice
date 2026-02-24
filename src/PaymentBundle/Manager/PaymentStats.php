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

namespace SolidInvoice\PaymentBundle\Manager;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateMalformedStringException;
use DateTime;
use Money\Currency;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidInvoice\PaymentBundle\Repository\PaymentRepository;

final readonly class PaymentStats
{
    public function __construct(
        private PaymentRepository $paymentRepository
    ) {
    }

    /**
     * Get comprehensive payment statistics for the dashboard.
     *
     * @return array{
     *     total_income: array<string, array{amount: BigInteger, currency: Currency}>,
     *     total_count: int,
     *     this_month: array<string, array{amount: BigInteger, currency: Currency}>,
     *     this_month_count: int,
     *     pending_count: int,
     *     failed_count: int,
     *     recent_payments: array<string, mixed>
     * }
     * @throws MathException
     * @throws DateMalformedStringException
     */
    public function getStatistics(): array
    {
        return [
            'total_income' => $this->getTotalIncome(),
            'total_count' => $this->getTotalPaymentCount(),
            'this_month' => $this->getThisMonthIncome(),
            'this_month_count' => $this->getThisMonthPaymentCount(),
            'pending_count' => $this->getPaymentCountByStatus(PaymentStatus::Pending->value),
            'failed_count' => $this->getPaymentCountByStatus(PaymentStatus::Failed->value),
            'recent_payments' => $this->paymentRepository->getRecentPayments(5),
        ];
    }

    /**
     * Get total income with amount and currency separately.
     *
     * @return array<string, array{amount: BigInteger, currency: Currency}>
     * @throws MathException
     */
    private function getTotalIncome(): array
    {
        $totals = $this->paymentRepository->getTotalIncome();
        $result = [];

        foreach ($totals as $currencyCode => $amount) {
            $result[$currencyCode] = [
                'amount' => $amount,
                'currency' => new Currency($currencyCode),
            ];
        }

        return $result;
    }

    /**
     * Get this month's income with amount and currency separately.
     *
     * @return array<string, array{amount: BigInteger, currency: Currency}>
     * @throws MathException
     */
    private function getThisMonthIncome(): array
    {
        $totals = $this->paymentRepository->getPaymentsThisMonth();
        $result = [];

        foreach ($totals as $currencyCode => $amount) {
            $result[$currencyCode] = [
                'amount' => $amount,
                'currency' => new Currency($currencyCode),
            ];
        }

        return $result;
    }

    /**
     * Get total count of captured payments.
     */
    private function getTotalPaymentCount(): int
    {
        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', PaymentStatus::Captured->value);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get count of payments for current month.
     *
     * @throws DateMalformedStringException
     */
    private function getThisMonthPaymentCount(): int
    {
        $startOfMonth = new DateTime('first day of this month midnight');
        $endOfMonth = new DateTime('last day of this month 23:59:59');

        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->andWhere('p.created >= :start')
            ->andWhere('p.created <= :end')
            ->setParameter('status', PaymentStatus::Captured->value)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get count of payments by status.
     */
    private function getPaymentCountByStatus(string $status): int
    {
        $qb = $this->paymentRepository->createQueryBuilder('p');

        $qb->select('COUNT(p.id)')
            ->where('p.status = :status')
            ->setParameter('status', $status);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
