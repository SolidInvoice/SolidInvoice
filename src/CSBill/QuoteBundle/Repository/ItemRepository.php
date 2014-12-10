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

use CSBill\CoreBundle\Entity\Tax;
use Doctrine\ORM\EntityRepository;

/**
 * Class ItemRepository
 *
 * @package CSBill\QuoteBundle\Repository
 */
class ItemRepository extends EntityRepository
{
    /**
     * Removes all tax rates from invoices
     *
     * @param Tax $tax
     */
    public function removeTax(Tax $tax)
    {
        $qb = $this->createQueryBuilder('q')
            ->update()
            ->set('q.tax', 'NULL')
            ->where('q.tax = :tax')
            ->setParameter('tax', $tax);

        $qb->getQuery()->execute();
    }
}
