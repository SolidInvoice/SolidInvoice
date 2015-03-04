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
            $qb->where('q.status = :status')
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

        $qb
            ->innerJoin('q.client', 'c')
            ->orderBy('q.created', 'DESC')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
