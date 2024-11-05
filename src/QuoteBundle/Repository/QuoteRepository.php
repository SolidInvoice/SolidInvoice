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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\QuoteBundle\Entity\Quote;

/**
 * @extends ServiceEntityRepository<Quote>
 */
class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    public function getTotalQuotes(string $status = null): int
    {
        $qb = $this->createQueryBuilder('q');

        $qb->select('COUNT(q.id)');

        if (null !== $status) {
            $qb->where('q.status = :status')
                ->setParameter('status', $status);
        }

        $query = $qb->getQuery();

        try {
            return (int) $query->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
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
            ->orderBy('q.created', Criteria::DESC)
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
     * @param list<int> $ids
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
}
