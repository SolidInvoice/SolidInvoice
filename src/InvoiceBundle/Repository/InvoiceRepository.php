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
use DateTimeImmutable;
use DateTimeInterface;
use Deprecated;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Generator;
use Psr\Clock\ClockInterface;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceReminder;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\SettingsBundle\Entity\Setting;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function intval;
use function strval;

/**
 * @extends EntityRepository<Invoice>
 * @see \SolidInvoice\InvoiceBundle\Tests\Repository\InvoiceRepositoryTest
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
     */
    #[Deprecated(message: 'This function is deprecated, and the one in PaymentRepository should be used instead')]
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
     * Counts non-recurring invoices created in the calendar month containing
     * `$date`. Used by the `invoices_per_month` quota gate. Scoping to the
     * active company is handled by the global `CompanyFilter`.
     */
    public function countCreatedInMonth(DateTimeInterface $date): int
    {
        $monthStart = (new DateTimeImmutable($date->format('Y-m-01 00:00:00'), $date->getTimezone()));
        $monthEnd = $monthStart->modify('+1 month');

        try {
            return (int) $this->createQueryBuilder('i')
                ->select('COUNT(i)')
                ->where('i.created >= :start')
                ->andWhere('i.created < :end')
                ->setParameter('start', $monthStart)
                ->setParameter('end', $monthEnd)
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
            if ($entity instanceof Invoice) {
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
     * Get pending invoices needing pre-due reminders, across every company at once.
     *
     * Companies opt in through their settings, so those are joined here rather than resolved by the
     * caller: one query covers the whole tenant base instead of one query per tenant. The caller is
     * responsible for suspending the company filter, otherwise this is scoped to a single company.
     *
     * Only the fields needed to dispatch a reminder are selected — hydrating entities for a scan
     * this wide would hold every matched invoice in the identity map.
     *
     * @return iterable<array{invoiceId: Ulid, companyId: Ulid, due: DateTimeImmutable|null}>
     * @throws DateMalformedStringException
     */
    public function getInvoicesNeedingPreDueReminders(int $daysBeforeDue): iterable
    {
        $settingValues = $this->preDueSettingValuesFor($daysBeforeDue);

        if ($settingValues === []) {
            return [];
        }

        $targetDate = $this->clock->now()->modify(sprintf('+%d days', $daysBeforeDue));

        $qb = $this->createReminderCandidateQueryBuilder(ReminderType::PreDue, $targetDate);

        $qb
            ->innerJoin(Setting::class, 's_pre_due', 'WITH', 's_pre_due.company = c AND s_pre_due.key = :preDueKey AND s_pre_due.value = :enabled')
            ->innerJoin(Setting::class, 's_days', 'WITH', 's_days.company = c AND s_days.key = :daysKey AND s_days.value IN (:days)')
            ->andWhere('i.status = :status')
            ->setParameter('status', InvoiceStatus::Pending)
            ->setParameter('preDueKey', 'invoice/reminder/pre_due_enabled')
            ->setParameter('daysKey', 'invoice/reminder/pre_due_days')
            ->setParameter('days', $settingValues);

        return $this->hydrateReminderCandidates($qb->getQuery()->toIterable([], AbstractQuery::HYDRATE_SCALAR));
    }

    /**
     * Get overdue invoices needing reminders, across every company at once.
     *
     * @return iterable<array{invoiceId: Ulid, companyId: Ulid, due: DateTimeImmutable|null}>
     * @throws DateMalformedStringException
     * @see self::getInvoicesNeedingPreDueReminders() for why this scans all companies at once.
     */
    public function getInvoicesNeedingOverdueReminders(int $daysOverdue, ReminderType $reminderType): iterable
    {
        $targetDate = $this->clock->now()->modify(sprintf('-%d days', $daysOverdue));

        $qb = $this->createReminderCandidateQueryBuilder($reminderType, $targetDate);

        $qb
            ->andWhere('i.status IN (:statuses)')
            ->setParameter('statuses', [InvoiceStatus::Pending, InvoiceStatus::Overdue]);

        return $this->hydrateReminderCandidates($qb->getQuery()->toIterable([], AbstractQuery::HYDRATE_SCALAR));
    }

    /**
     * Scalar hydration hands back raw driver values — a 16 byte binary string for a ULID on MySQL,
     * a uuid string on Postgres, and a plain date string for the due date. Run them back through
     * the mapped Doctrine types so callers get the same objects entity hydration would have given
     * them, without the identity map cost of hydrating whole invoices.
     *
     * @param iterable<array{invoiceId: mixed, companyId: mixed, due: mixed}> $rows
     * @return Generator<int, array{invoiceId: Ulid, companyId: Ulid, due: DateTimeImmutable|null}>
     */
    private function hydrateReminderCandidates(iterable $rows): Generator
    {
        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        $ulidType = Type::getType(UlidType::NAME);
        $dateType = Type::getType(Types::DATE_IMMUTABLE);

        foreach ($rows as $row) {
            yield [
                'invoiceId' => $ulidType->convertToPHPValue($row['invoiceId'], $platform),
                'companyId' => $ulidType->convertToPHPValue($row['companyId'], $platform),
                'due' => $dateType->convertToPHPValue($row['due'], $platform),
            ];
        }
    }

    /**
     * Invoices due on an exact date that have not had this reminder type recorded yet, limited to
     * companies with reminders switched on.
     *
     * The anti-join needs no company predicate of its own — an invoice id already identifies a
     * single tenant's row.
     */
    private function createReminderCandidateQueryBuilder(ReminderType $reminderType, DateTimeInterface $targetDate): QueryBuilder
    {
        return $this->createQueryBuilder('i')
            ->select('i.id AS invoiceId', 'c.id AS companyId', 'i.due AS due')
            ->innerJoin('i.company', 'c')
            ->innerJoin(Setting::class, 's_enabled', 'WITH', 's_enabled.company = c AND s_enabled.key = :enabledKey AND s_enabled.value = :enabled')
            ->leftJoin(InvoiceReminder::class, 'r', 'WITH', 'r.invoice = i.id AND r.reminderType = :reminderType')
            ->where('i.due = :targetDate')
            ->andWhere('r.id IS NULL')
            ->setParameter('targetDate', $targetDate, Types::DATE_IMMUTABLE)
            ->setParameter('reminderType', $reminderType)
            ->setParameter('enabledKey', 'invoice/reminder/enabled')
            ->setParameter('enabled', '1');
    }

    /**
     * The distinct pre-due windows configured across all companies.
     *
     * Pre-due reminders fire a company-configured number of days before the due date, so the scan
     * runs once per distinct window rather than once per company.
     *
     * @return list<int>
     */
    public function getConfiguredPreDueDays(): array
    {
        return array_values(array_unique(array_map(intval(...), $this->preDueDaySettingValues())));
    }

    /**
     * Every raw spelling of the setting that normalises to the given window.
     *
     * The scan groups companies by the normalised value, so it has to match on those same raw
     * spellings. The column is free text, so "3", "03" and "3 days" all mean the same window —
     * comparing against a single canonical string would silently skip the companies that stored
     * one of the others.
     *
     * @return list<string>
     */
    private function preDueSettingValuesFor(int $daysBeforeDue): array
    {
        return array_values(
            array_filter(
                $this->preDueDaySettingValues(),
                static fn (string $value): bool => intval($value) === $daysBeforeDue
            )
        );
    }

    /**
     * The distinct raw pre-due day settings stored across all companies.
     *
     * @return list<string>
     */
    private function preDueDaySettingValues(): array
    {
        $values = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('DISTINCT s.value')
            ->from(Setting::class, 's')
            ->where('s.key = :daysKey')
            ->andWhere('s.value IS NOT NULL')
            ->setParameter('daysKey', 'invoice/reminder/pre_due_days')
            ->getQuery()
            ->getSingleColumnResult();

        return array_values(array_map(strval(...), $values));
    }
}
