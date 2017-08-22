<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Repository;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\QuoteBundle\Entity\Quote;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class QuoteRepository extends EntityRepository
{
    /**
     * Gets total number of quotes.
     *
     * @param string $status
     *
     * @return int
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
     *
     * @return array
     */
    public function getRecentQuotes($limit = 5): array
    {
        $qb = $this->createQueryBuilder('q');

        $qb
            ->innerJoin('q.client', 'c')
            ->orderBy('q.created', 'DESC')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getResult();
    }

    /**
     * @param array $parameters
     *
     * @return QueryBuilder
     */
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

    /**
     * @return QueryBuilder
     */
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
     * @param Client $client
     */
    public function updateCurrency(Client $client)
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');
        $filters->disable('softdeleteable');

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
                ->from('SolidInvoiceQuoteBundle:Item', 'qt')
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
        $filters->enable('softdeleteable');
    }

    /**
     * Delete multiple quotes based on IDs.
     *
     * @param array $ids
     */
    public function deleteQuotes(array $ids): void
    {
        $filters = $this->getEntityManager()->getFilters();
        $filters->disable('archivable');

        $em = $this->getEntityManager();

        /** @var Quote[] $quotes */
        $quotes = $this->findBy(['id' => $ids]);

        array_walk($quotes, [$em, 'remove']);

        $em->flush();

        $filters->enable('archivable');
    }
}
