<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use SolidInvoice\QuoteBundle\Entity\Item;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * Class ItemRepository.
 */
class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

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
