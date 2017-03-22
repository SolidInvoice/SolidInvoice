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

namespace CSBill\QuoteBundle\Repository;

use CSBill\QuoteBundle\Entity\Quote;
use CSBill\TaxBundle\Entity\Tax;
use Doctrine\ORM\EntityRepository;

/**
 * Class ItemRepository.
 */
class ItemRepository extends EntityRepository
{
    /**
     * Removes all tax rates from invoices.
     *
     * @param Tax $tax
     */
    public function removeTax(Tax $tax)
    {
        if (Tax::TYPE_EXCLUSIVE === $tax->getType()) {
            $qb = $this->createQueryBuilder('i');

            $query = $qb->where('i.tax = :tax')
                ->setParameter('tax', $tax)
                ->groupBy('i.quote')
                ->getQuery();

            /** @var Quote $quote */
            foreach ($query->execute() as $quote) {
                $quote->setTotal($quote->getBaseTotal() + $quote->getTax());
                $quote->setTax(null);
                $this->getEntityManager()->persist($quote);
            }

            $this->getEntityManager()->flush();
        }

        $qb = $this->createQueryBuilder('q')
            ->update()
            ->set('q.tax', 'NULL')
            ->where('q.tax = :tax')
            ->setParameter('tax', $tax);

        $qb->getQuery()->execute();
    }
}
