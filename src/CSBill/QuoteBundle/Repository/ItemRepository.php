<?php
/**
 * This file is part of the MiWay Business Insurance project.
 *
 * @author      MiWay Development Team
 * @copyright   Copyright (c) 2014 MiWay Insurance Ltd
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
