<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Repository;

use Doctrine\ORM\EntityRepository;

class QuoteRepository extends EntityRepository
{
    /**
     * Gets total number of quotes.
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
     * Gets the most recent created quotes.
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

    /**
     * @param array $parameters
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getGridQuery(array $parameters = [])
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
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getArchivedGridQuery()
    {
	$this->getEntityManager()->getFilters()->disable('archivable');

	$qb = $this->createQueryBuilder('q');

	$qb->select(['q', 'c'])
	    ->join('q.client', 'c')
	    ->where('q.archived is not null');

	return $qb;
    }
}
