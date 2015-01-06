<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class QuoteRepository extends EntityRepository
{
    /**
     * Gets total number of quotes
     *
     * @param string $status
     *
     * @return int
     */
    public function getTotalQuotes($status = null)
    {
        $qb = $this->createQueryBuilder('q');

        $qb->select('COUNT(q.id)');

        if (null !== $status) {
            $qb->join('q.status', 's')
                ->where('s.name = :status')
                ->setParameter('status', $status);
        }

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Gets the most recent created quotes
     *
     * @param int $limit
     *
     * @return array
     */
    public function getRecentQuotes($limit = 5)
    {
        $qb = $this->createQueryBuilder('q');

        $qb->select(
            'q.id',
            'c.name as client',
            'c.id as client_id',
            'q.total',
            'q.created',
            's.name as status',
            's.label as status_label'
        )
            ->join('q.client', 'c')
            ->join('q.status', 's')
            ->orderBy('q.created', 'DESC')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }
}
