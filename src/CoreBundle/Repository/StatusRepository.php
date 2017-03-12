<?php

declare(strict_types=1);
/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;

abstract class StatusRepository extends EntityRepository
{
    /**
     * Gets an array of all the available statuses.
     */
    public function getStatusList()
    {
        $queryBuilder = $this->createQueryBuilder('s')
            ->select('s.name');

        $query = $queryBuilder->getQuery();

        $query->useQueryCache(true)
            ->useResultCache(true, (60 * 60 * 24 * 7), 'status_list');

        return $query->getArrayResult();
    }
}
