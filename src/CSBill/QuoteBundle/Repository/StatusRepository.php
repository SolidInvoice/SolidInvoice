<?php

namespace CSBill\QuoteBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CSBill\CoreBundle\Util\ArrayUtil;

class StatusRepository extends EntityRepository
{
    /**
     * Returns an array of all the statuses
     *
     * @return array
     */
    public function findList()
    {
        $qb = $this->createQueryBuilder('s')
                   ->orderBy('s.name', 'ASC');

        $query = $qb->getQuery();

        $query->useQueryCache(true)
              ->useResultCache(true, (60 * 60 * 24 * 7), 'quote_status_list');

        $result = $query->getArrayResult();

        return ArrayUtil::column($result, 'name');
    }
}
