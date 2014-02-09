<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    public function getTotalClients()
    {
        $qb = $this->createQueryBuilder('c');

        $qb->select('COUNT(c.id)');

        $query = $qb->getQuery();

        return (int) $query->getSingleScalarResult();
    }
}
