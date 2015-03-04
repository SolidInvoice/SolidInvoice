<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Repository;

use Doctrine\ORM\EntityRepository;

class TaxRepository extends EntityRepository
{
    /**
     * Gets an array of all the available tax rates
     */
    public function getTotal()
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * Gets an array of all the available tax rates
     */
    public function getTaxList()
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select(
                array(
                    't.name',
                    't.rate',
                    't.type',
                )
            );

        $query = $queryBuilder->getQuery();

        $query->useQueryCache(true)
            ->useResultCache(true, (60 * 60 * 24), 'tax_list');

        return $query->getArrayResult();
    }
}
