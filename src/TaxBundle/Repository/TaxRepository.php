<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\TaxBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class TaxRepository extends EntityRepository
{
    /**
     * Gets an array of all the available tax rates.
     *
     * @return array
     */
    public function getTaxList(): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select(
                [
                    't.name',
                    't.rate',
                    't.type',
                ]
            );

        $query = $queryBuilder->getQuery();

        $query->useQueryCache(true)
            ->useResultCache(true, (60 * 60 * 24), 'tax_list');

        return $query->getArrayResult();
    }

    /**
     * @return bool
     */
    public function taxRatesConfigured(): bool
    {
        return $this->getTotal() > 0;
    }

    /**
     * Gets an array of all the available tax rates.
     *
     * @return int
     */
    public function getTotal(): int
    {
        $queryBuilder = $this->createQueryBuilder('t')
        ->select('COUNT(t.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('t');

        return $qb;
    }
}
