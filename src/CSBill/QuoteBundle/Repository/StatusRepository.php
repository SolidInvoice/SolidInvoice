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
