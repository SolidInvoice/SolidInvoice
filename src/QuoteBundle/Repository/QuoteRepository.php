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

namespace SolidInvoice\QuoteBundle\Repository;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Component\Uid\Ulid;
use function array_column;
use function sprintf;

/**
 * @extends EntityRepository<Quote>
 */
class QuoteRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    /**
     * Backs the UniqueEntity constraint on Quote::$quoteId. Archived documents keep their
     * number, so they count; a blank number is not a number, so it never collides. The
     * CompanyFilter scopes the lookup to the current company.
     *
     * @param array<string, mixed> $criteria
     *
     * @return list<Quote>
     */
    public function findByQuoteIdIncludingArchived(array $criteria): array
    {
        if (($criteria['quoteId'] ?? '') === '') {
            return [];
        }

        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        try {
            return array_values($this->findBy($criteria));
        } finally {
            $filters->enable('archivable');
        }
    }

    public function getTotalQuotes(?QuoteStatus $status = null): int
    {
        $qb = $this->createQueryBuilder('q');

        $qb->select('COUNT(q.id)');

        if ($status instanceof QuoteStatus) {
            $qb->where('q.status = :status')
                ->setParameter('status', $status);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return 0;
        }
    }

    /**
     * @return Quote[]
     */
    public function getRecentQuotes(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('q');

        $qb
            ->innerJoin('q.client', 'c')
            ->orderBy('q.created', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array{client?: string} $parameters
     */
    public function getGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('q');

        $qb->select(['q', 'c'])
            ->join('q.client', 'c');

        if (! empty($parameters['client'])) {
            $qb->where('q.client = :client')
                ->setParameter('client', $parameters['client']);
        }

        return $qb;
    }

    public function getArchivedGridQuery(): QueryBuilder
    {
        $this->getEntityManager()->getFilters()->disable('archivable');

        $qb = $this->createQueryBuilder('q');

        $qb->select(['q', 'c'])
            ->join('q.client', 'c')
            ->where('q.archived is not null');

        return $qb;
    }

    /**
     * @param list<string> $ids
     */
    public function deleteQuotes(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $em = $this->getEntityManager();

        foreach ($ids as $id) {
            $entity = $this->find($id);
            if (! $entity instanceof Quote) {
                continue;
            }

            $em->remove($entity);
        }

        $em->flush();

        $filters->enable('archivable');
    }

    /**
     * @param list<string> $ids
     */
    public function archiveQuotes(array $ids): void
    {
        $em = $this->getEntityManager();

        foreach ($ids as $id) {
            $quote = $this->find($id);

            if (! $quote instanceof Quote) {
                continue;
            }

            $quote->setArchived(true);

            $em->persist($quote);
        }

        $em->flush();
    }

    /**
     * @param list<int> $ids
     */
    public function restoreQuotes(array $ids): void
    {
        $em = $this->getEntityManager();

        $em->getFilters()->disable('archivable');

        foreach ($ids as $id) {
            $quote = $this->find($id);

            if (! $quote instanceof Quote) {
                continue;
            }

            $quote->setArchived(null);

            $em->persist($quote);
        }

        $em->flush();

        $em->getFilters()->enable('archivable');
    }

    /**
     * Get pending quotes with client information for dashboard.
     *
     * @return Quote[]
     */
    public function getPendingQuotes(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('q');

        $qb
            ->innerJoin('q.client', 'c')
            ->addSelect('c')
            ->where('q.status = :status')
            ->setParameter('status', QuoteStatus::Pending)
            ->orderBy('q.created', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Get recently responded quotes (accepted or declined) for activity feed.
     *
     * @return Quote[]
     */
    public function getRecentlyRespondedQuotes(int $limit = 5): array
    {
        $qb = $this->createQueryBuilder('q');

        $qb
            ->innerJoin('q.client', 'c')
            ->addSelect('c')
            ->where('q.status = :acceptedStatus OR q.status = :declinedStatus')
            ->setParameter('acceptedStatus', QuoteStatus::Accepted)
            ->setParameter('declinedStatus', QuoteStatus::Declined)
            ->orderBy('q.updated', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * Companies holding at least one pending quote whose last valid day is before `$today`.
     *
     * Runs across every tenant, so the caller must suspend the company filter.
     *
     * @return list<Ulid>
     */
    public function getCompanyIdsWithQuotesToExpire(DateTimeInterface $today): array
    {
        // Array, not single-column, hydration: only it converts the column to a Ulid.
        return array_column($this->createExpirableQueryBuilder($today)
            ->select('DISTINCT c.id AS id')
            ->innerJoin('q.company', 'c')
            ->getQuery()
            ->getArrayResult(), 'id');
    }

    /**
     * IDs of the current company's pending quotes whose last valid day is before `$today`.
     * Only IDs, so a company with thousands of them does not fill the identity map.
     *
     * @return list<Ulid>
     */
    public function getIdsOfQuotesToExpire(DateTimeInterface $today): array
    {
        return array_column($this->createExpirableQueryBuilder($today)
            ->select('q.id AS id')
            ->getQuery()
            ->getArrayResult(), 'id');
    }

    /**
     * Pending quotes whose validity ends between today and `$today + $days`, soonest first.
     *
     * @return Quote[]
     */
    public function getQuotesExpiringSoon(DateTimeInterface $today, int $days, int $limit = 5): array
    {
        return $this->createExpiringSoonQueryBuilder($today, $days)
            ->innerJoin('q.client', 'c')
            ->addSelect('c')
            ->orderBy('q.due', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countQuotesExpiringSoon(DateTimeInterface $today, int $days): int
    {
        return (int) $this->createExpiringSoonQueryBuilder($today, $days)
            ->select('COUNT(q.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createExpirableQueryBuilder(DateTimeInterface $today): QueryBuilder
    {
        return $this->createQueryBuilder('q')
            ->where('q.status = :status')
            ->andWhere('q.due < :today')
            ->setParameter('status', QuoteStatus::Pending)
            ->setParameter('today', $today, Types::DATE_IMMUTABLE);
    }

    private function createExpiringSoonQueryBuilder(DateTimeInterface $today, int $days): QueryBuilder
    {
        $until = DateTimeImmutable::createFromInterface($today)->modify(sprintf('+%d days', $days));

        return $this->createQueryBuilder('q')
            ->where('q.status = :status')
            ->andWhere('q.due >= :today')
            ->andWhere('q.due <= :until')
            ->setParameter('status', QuoteStatus::Pending)
            ->setParameter('today', $today, Types::DATE_IMMUTABLE)
            ->setParameter('until', $until, Types::DATE_IMMUTABLE);
    }
}
