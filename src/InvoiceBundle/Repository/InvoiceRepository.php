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
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use DateMalformedStringException;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<Invoice>
 */
class InvoiceRepository extends EntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClockInterface $clock
    ) {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Get the total amount for paid invoices.
     *
     * @throws MathException
     * @deprecated This function is deprecated, and the one in PaymentRepository should be used instead
     */
    public function getTotalIncome(?Client $client = null): BigNumber
    {
        @trigger_error(
            'This function is deprecated, and the one in PaymentRepository should be used instead',
            E_USER_DEPRECATED
        );

        return $this->getTotalByStatus(InvoiceStatus::Paid, $client);
    }

    /**
     * Get the total amount for a specific invoice status.
     *
     * @throws MathException
     */
    public function getTotalByStatus(InvoiceStatus $status, ?Client $client = null): BigNumber
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.total)')
            ->where('i.status = :status')
            ->setParameter('status', $status);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UlidType::NAME);
        }

        try {
            return BigNumber::of($qb->getQuery()->getSingleResult());
        } catch (NoResultException | NonUniqueResultException) {
            return BigInteger::zero();
        }
    }

    /**
     * Get the total amount for outstanding invoices.
     */
    public function getTotalOutstanding(?Client $client = null): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance)')
            ->where('i.status = :status')
            ->setParameter('status', InvoiceStatus::Pending);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    public function getTotalInvoices(): int
    {
        try {
            return (int) $this->createQueryBuilder('i')
                ->select('COUNT(i)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Get the total number of invoices for a specific status.
     *
     * @param InvoiceStatus|InvoiceStatus[] $status
     */
    public function getCountByStatus(InvoiceStatus | array $status, ?Client $client = null): int
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('COUNT(i)');

        if (is_array($status)) {
            $qb->add('where', $qb->expr()->in('i.status', ':status'));
        } else {
            $qb->where('i.status = :status');
        }

        $qb->setParameter('status', $status);

        if ($client instanceof Client) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $client->getId(), UlidType::NAME);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * Gets the most recent created invoices.
     *
     * @return Invoice[]
     */
    public function getRecentInvoices(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->orderBy('i.created', Criteria::DESC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{client?: Client} $parameters
     */
    public function getGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select(['i', 'c'])
            ->join('i.client', 'c');

        if (! empty($parameters['client'])) {
            $qb->andWhere('i.client = :client')
                ->setParameter('client', $parameters['client'], UlidType::NAME);
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
     * @param list<string> $ids
     */
    public function deleteInvoices(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $em = $this->getEntityManager();

        array_walk($ids, function (string $id) use ($em): void {
            $entity = $this->find($id);
            if ($entity) {
                $em->remove($entity);
            }
        });

        $em->flush();

        $filters->enable('archivable');
    }

    /**
     * Checks if an invoice is paid in full.
     */
    public function isFullyPaid(Invoice $invoice): bool
    {
        $invoiceTotal = $invoice->getTotal();

        $totalPaid = $this->getEntityManager()
            ->getRepository(Payment::class)
            ->getTotalPaidForInvoice($invoice);

        return $totalPaid->isEqualTo($invoiceTotal) || $totalPaid->isGreaterThan($invoiceTotal);
    }

    public function getTotalOutstandingForClient(Client $client): BigInteger
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance) as total')
            ->where('i.status = :status')
            ->andWhere('i.client = :client')
            ->setParameter('client', $client->getId(), UlidType::NAME)
            ->setParameter('status', InvoiceStatus::Pending);

        $query = $qb->getQuery();

        try {
            return BigInteger::of((string) $query->getSingleScalarResult());
        } catch (MathException | NoResultException | NonUniqueResultException) {
            return BigInteger::zero();
        }
    }

    /**
     * @param list<int> $ids
     */
    public function archiveInvoices(array $ids): void
    {
        $em = $this->getEntityManager();

        foreach ($ids as $id) {
            $invoice = $this->find($id);

            if (! $invoice instanceof Invoice) {
                continue;
            }

            $invoice->setArchived(true);

            $em->persist($invoice);
        }

        $em->flush();
    }

    /**
     * @param list<int> $ids
     */
    public function restoreInvoices(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        foreach ($ids as $id) {
            $invoice = $this->find($id);

            if (! $invoice instanceof Invoice) {
                continue;
            }

            $invoice->setArchived(null);

            $em->persist($invoice);
        }

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    /**
     * Get overdue invoices with client information for dashboard.
     *
     * @return Invoice[]
     */
    public function getOverdueInvoices(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->addSelect('c')
            ->where('i.status = :status')
            ->setParameter('status', InvoiceStatus::Overdue)
            ->orderBy('i.due', Criteria::ASC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get draft invoices with client information for dashboard.
     *
     * @return Invoice[]
     */
    public function getDraftInvoices(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->addSelect('c')
            ->where('i.status = :status')
            ->setParameter('status', InvoiceStatus::Draft)
            ->orderBy('i.created', Criteria::DESC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get total outstanding amount grouped by currency for dashboard stats.
     *
     * @return array<string, BigInteger>
     * @throws MathException
     */
    public function getTotalOutstandingByCurrency(): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance) as total', 'c.currencyCode')
            ->innerJoin('i.client', 'c')
            ->where('i.status = :pendingStatus OR i.status = :overdueStatus')
            ->setParameter('pendingStatus', InvoiceStatus::Pending)
            ->setParameter('overdueStatus', InvoiceStatus::Overdue)
            ->groupBy('c.currencyCode');

        $results = [];
        foreach ($qb->getQuery()->getArrayResult() as $result) {
            if (null !== $result['currencyCode'] && '' !== $result['currencyCode'] && null !== $result['total']) {
                $results[$result['currencyCode']] = BigInteger::of($result['total']);
            }
        }

        return $results;
    }

    /**
     * Get overdue invoice totals grouped by currency for dashboard stats.
     *
     * @return array<string, BigInteger>
     * @throws MathException
     */
    public function getOverdueAmountByCurrency(): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('SUM(i.balance) as total', 'c.currencyCode')
            ->innerJoin('i.client', 'c')
            ->where('i.status = :status')
            ->setParameter('status', InvoiceStatus::Overdue)
            ->groupBy('c.currencyCode');

        $results = [];
        foreach ($qb->getQuery()->getArrayResult() as $result) {
            if (null !== $result['currencyCode'] && '' !== $result['currencyCode'] && null !== $result['total']) {
                $results[$result['currencyCode']] = BigInteger::of($result['total']);
            }
        }

        return $results;
    }

    /**
     * Get invoice count by status for distribution chart.
     *
     * @return array<string, int>
     */
    public function getCountByStatusAll(): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb->select('i.status', 'COUNT(i.id) as count')
            ->groupBy('i.status');

        $results = [];
        foreach ($qb->getQuery()->getArrayResult() as $result) {
            $status = $result['status'];
            $results[$status instanceof InvoiceStatus ? $status->value : $status] = (int) $result['count'];
        }

        return $results;
    }

    /**
     * Get recently sent invoices (pending status) for activity feed.
     *
     * @return Invoice[]
     */
    public function getRecentlySentInvoices(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->addSelect('c')
            ->where('i.status = :status')
            ->setParameter('status', InvoiceStatus::Pending)
            ->orderBy('i.updated', Criteria::DESC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get recently generated recurring invoices for activity feed.
     *
     * @return Invoice[]
     */
    public function getRecentRecurringGeneratedInvoices(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('i');

        $qb
            ->innerJoin('i.client', 'c')
            ->addSelect('c')
            ->innerJoin('i.recurringInvoice', 'ri')
            ->addSelect('ri')
            ->orderBy('i.created', Criteria::DESC)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get pending invoices that are past their due date.
     * Uses toIterable() for memory-efficient streaming.
     *
     * @return iterable<Invoice>
     */
    public function getPendingOverdueInvoices(): iterable
    {
        $qb = $this->createQueryBuilder('i');

        $qb->where('i.status = :status')
            ->andWhere('i.due < :now')
            ->andWhere('i.due IS NOT NULL')
            ->setParameter('status', InvoiceStatus::Pending)
            ->setParameter('now', $this->clock->now());

        return $qb->getQuery()->toIterable();
    }

    /**
     * Get pending invoices needing pre-due reminders.
     *
     * @return iterable<Invoice>
     * @throws DateMalformedStringException
     */
    public function getInvoicesNeedingPreDueReminders(int $daysBeforeDue): iterable
    {
        $targetDate = $this->clock->now()->modify("+{$daysBeforeDue} days");

        $qb = $this->createQueryBuilder('i');

        $qb->leftJoin(InvoiceReminder::class, 'r', 'WITH', 'r.invoice = i.id AND r.reminderType = :reminderType')
            ->where('i.status = :status')
            ->andWhere('i.due = :targetDate')
            ->andWhere('r.id IS NULL')
            ->setParameter('status', InvoiceStatus::Pending)
            ->setParameter('targetDate', $targetDate, Types::DATE_IMMUTABLE)
            ->setParameter('reminderType', ReminderType::PreDue);

        return $qb->getQuery()->toIterable();
    }

    /**
     * Get overdue invoices needing reminders.
     *
     * @return iterable<Invoice>
     * @throws DateMalformedStringException
     */
    public function getInvoicesNeedingOverdueReminders(int $daysOverdue, ReminderType $reminderType): iterable
    {
        $targetDate = $this->clock->now()->modify("-{$daysOverdue} days");

        $qb = $this->createQueryBuilder('i');

        $qb->leftJoin(InvoiceReminder::class, 'r', 'WITH', 'r.invoice = i.id AND r.reminderType = :reminderType')
            ->where('i.status  in (:pending, :overdue)')
            ->andWhere('i.due = :targetDate')
            ->andWhere('r.id IS NULL')
            ->setParameter('pending', InvoiceStatus::Pending)
            ->setParameter('overdue', InvoiceStatus::Overdue)
            ->setParameter('targetDate', $targetDate, Types::DATE_IMMUTABLE)
            ->setParameter('reminderType', $reminderType);

        return $qb->getQuery()->toIterable();
    }
}
