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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\QuoteBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Quote;

class QuoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Quote::class);
    }

    /**
     * Gets total number of quotes.
     *
     * @param string $status
     */
    public function getTotalQuotes(string $status = null): int
    {
        $qb = $this->createQueryBuilder('q');

        $qb->select('COUNT(q.id)');

        if (null !== $status) {
            $qb->where('q.status = :status')
                ->setParameter('status', $status);
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Gets the most recent created quotes.
     *
     * @param int $limit
     */
    public function getRecentQuotes($limit = 5): array
    {
        $qb = $this->createQueryBuilder('q');

        $qb
            ->innerJoin('q.client', 'c')
            ->orderBy('q.created', Criteria::DESC)
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    public function getGridQuery(array $parameters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('q');

        $qb->select(['q', 'c'])
            ->join('q.client', 'c');

        if (!empty($parameters['client'])) {
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

    public function updateCurrency(Client $client)
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $currency = $client->getCurrency();

        $qb = $this->createQueryBuilder('q');

        $qb->update()
            ->set('q.total.currency', ':currency')
            ->set('q.baseTotal.currency', ':currency')
            ->set('q.tax.currency', ':currency')
            ->where('q.client = :client')
            ->setParameter('client', $client)
            ->setParameter('currency', $currency);

        if ($qb->getQuery()->execute()) {
            $qbi = $this->getEntityManager()->createQueryBuilder();

            $qbi->update()
                ->from(Item::class, 'qt')
                ->set('qt.price.currency', ':currency')
                ->set('qt.total.currency', ':currency')
                ->where(
                    $qbi->expr()->in(
                        'qt.quote',
                        $this->createQueryBuilder('q')
                            ->select('q.id')
                            ->where('q.client = :client')
                            ->getDQL()
                    )
                )
                ->setParameter('client', $client)
                ->setParameter('currency', $currency);

            $qbi->getQuery()->execute();
        }

        $filters->enable('archivable');
    }

    /**
     * Delete multiple quotes based on IDs.
     */
    public function deleteQuotes(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $em = $this->getEntityManager();

        /** @var Quote[] $quotes */
        $quotes = $this->findBy(['id' => $ids]);

        array_walk($quotes, function (object $entity) use ($em): void {
            $em->remove($entity);
        });

        $em->flush();

        $filters->enable('archivable');
    }
}
