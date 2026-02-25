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

namespace SolidInvoice\InvoiceBundle\Repository;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CronBundle\Enum\ScheduleRecurringType;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;

/**
 * @extends ServiceEntityRepository<RecurringInvoice>
 */
class RecurringInvoiceRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly RecurringSchedule $recurringSchedule,
        private readonly ClockInterface $clock
    ) {
        parent::__construct($registry, RecurringInvoice::class);
    }

    /**
     * @param array{client?: Client} $parameters
     */
    public function getRecurringGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c');

        if (! empty($parameters['client'])) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $parameters['client']);
        }

        return $qb;
    }

    public function getArchivedGridQuery(): QueryBuilder
    {
        $this->getEntityManager()->getFilters()->disable('archivable');

        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c')
            ->where('i.archived is not null');

        return $qb;
    }

    /**
     * @param list<int> $ids
     */
    public function deleteInvoices(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        try {
            $em = $this->getEntityManager();

            /** @var RecurringInvoice[] $invoices */
            $invoices = $this->findBy(['id' => $ids]);

            foreach ($invoices as $invoice) {
                $em->remove($invoice);
            }

            $em->flush();
        } finally {
            $filters->enable('archivable');
        }
    }

    /**
     * Restore archived recurring invoices.
     *
     * @param list<int> $ids
     */
    public function restoreInvoices(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        try {
            foreach ($ids as $id) {
                $invoice = $this->find($id);

                if (! $invoice instanceof RecurringInvoice) {
                    continue;
                }

                $invoice->setArchived(null);

                $em->persist($invoice);
            }

            $em->flush();
        } finally {
            $em->getFilters()->enable('archivable');
        }
    }

    /**
     * @return iterable<RecurringInvoice>
     */
    public function getActiveRecurringInvoices(): iterable
    {
        return $this->createQueryBuilder('ri')
            ->where('ri.status = :status')
            ->innerJoin('ri.recurringOptions', 'ro')
            ->setParameter('status', RecurringInvoiceStatus::Active->value)
            ->getQuery()
            ->toIterable();
    }

    /**
     * Get upcoming recurring invoices that will be generated within the next N days.
     *
     * @return RecurringInvoice[]
     */
    public function getUpcomingRecurringInvoices(int $days = 7, int $limit = 3): array
    {
        $now = new DateTime();
        $futureDate = new DateTime(sprintf('+%d days', $days));

        $qb = $this->createQueryBuilder('ri');

        $qb
            ->innerJoin('ri.client', 'c')
            ->addSelect('c')
            ->innerJoin('ri.recurringOptions', 'ro')
            ->addSelect('ro')
            ->where('ri.status = :status')
            ->andWhere('ri.dateStart <= :futureDate')
            ->andWhere('(ri.dateEnd IS NULL OR ri.dateEnd >= :now)')
            ->setParameter('status', RecurringInvoiceStatus::Active->value)
            ->setParameter('now', $now)
            ->setParameter('futureDate', $futureDate)
            ->orderBy('ri.dateStart', Criteria::ASC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get the total number of recurring invoices for a specific status.
     */
    public function getCountByStatus(RecurringInvoiceStatus $status): int
    {
        $qb = $this->createQueryBuilder('ri');

        $qb->select('COUNT(ri)')
            ->where('ri.status = :status')
            ->setParameter('status', $status->value);

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Count recurring invoices that will generate in the next N days.
     */
    public function getUpcomingCount(int $days = 7): int
    {
        $now = $this->clock->now();
        $count = 0;

        /** @var RecurringInvoice[] $activeInvoices */
        $activeInvoices = $this->createQueryBuilder('ri')
            ->where('ri.status = :status')
            ->innerJoin('ri.recurringOptions', 'ro')
            ->addSelect('ro')
            ->setParameter('status', RecurringInvoiceStatus::Active->value)
            ->getQuery()
            ->getResult();

        foreach ($activeInvoices as $invoice) {
            $nextRunDate = $this->recurringSchedule->getNextRunDate($invoice->getRecurringOptions());

            if ($nextRunDate && $nextRunDate->isAfter($now) && $nextRunDate->diffInDays($now) <= $days) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get the total number of invoices generated from recurring invoices.
     */
    public function getTotalGeneratedInvoices(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('COUNT(i)')
            ->from('SolidInvoice\InvoiceBundle\Entity\Invoice', 'i')
            ->where('i.recurringInvoice IS NOT NULL');

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Calculate Monthly Recurring Revenue (MRR) grouped by currency.
     * Normalizes non-monthly recurring invoices:
     * - DAILY × 30
     * - WEEKLY × 4.33 (52/12)
     * - MONTHLY × 1
     * - YEARLY ÷ 12
     *
     * @return array<string, BigInteger>
     * @throws MathException
     */
    public function getMonthlyRecurringRevenueByCurrency(): array
    {
        /** @var RecurringInvoice[] $activeInvoices */
        $activeInvoices = $this->createQueryBuilder('ri')
            ->innerJoin('ri.recurringOptions', 'ro')
            ->addSelect('ro')
            ->innerJoin('ri.client', 'c')
            ->addSelect('c')
            ->where('ri.status = :status')
            ->setParameter('status', RecurringInvoiceStatus::Active->value)
            ->getQuery()
            ->getResult();

        $results = [];

        foreach ($activeInvoices as $invoice) {
            $currencyCode = $invoice->getClient()->getCurrencyCode();
            $total = BigInteger::of((string) $invoice->getTotal());

            if (null === $currencyCode) {
                continue;
            }

            $recurringType = $invoice->getRecurringOptions()->getType();

            if (null === $recurringType) {
                continue;
            }

            // Normalize to monthly revenue
            $monthlyAmount = match ($recurringType) {
                ScheduleRecurringType::DAILY => $total->multipliedBy(30),
                ScheduleRecurringType::WEEKLY => $total->multipliedBy(433)->dividedBy(100), // × 4.33
                ScheduleRecurringType::MONTHLY => $total,
                ScheduleRecurringType::YEARLY => $total->dividedBy(12),
            };

            if (! isset($results[$currencyCode])) {
                $results[$currencyCode] = BigInteger::zero();
            }

            $results[$currencyCode] = $results[$currencyCode]->plus($monthlyAmount);
        }

        return $results;
    }
}
