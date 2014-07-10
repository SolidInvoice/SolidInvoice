<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ClientRepository
 *
 * Custom Repository class for managing clients
 */
class ClientRepository extends EntityRepository
{
    /**
     * Gets total number of clients
     *
     * @return int
     */
    public function getTotalClients()
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('COUNT(c.id)');

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }

    /**
     * Gets the most recent created clients
     *
     * @param int $limit
     *
     * @return array
     */
    public function getRecentClients($limit = 5)
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select(
            'c.id',
            'c.name',
            'c.website',
            'c.created',
            's.name as status',
            's.label as status_label'
        )
            ->join('c.status', 's')
            ->orderBy('c.created', 'DESC')
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getArrayResult();
    }
}
