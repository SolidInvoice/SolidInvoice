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

namespace SolidInvoice\InvoiceBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Item;
use SolidInvoice\TaxBundle\Entity\Tax;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    /**
     * Removes all tax rates from invoices.
     */
    public function removeTax(Tax $tax)
    {
        if (Tax::TYPE_EXCLUSIVE === $tax->getType()) {
            $qb = $this->createQueryBuilder('i');

            $query = $qb->where('i.tax = :tax')
                ->setParameter('tax', $tax)
                ->groupBy('i.invoice')
                ->getQuery();

            /** @var Invoice $invoice */
            foreach ($query->execute() as $invoice) {
                $invoice->setTotal($invoice->getBaseTotal() + $invoice->getTax());
                $invoice->setTax(null);
                $this->getEntityManager()->persist($invoice);
            }

            $this->getEntityManager()->flush();
        }

        $qb = $this->createQueryBuilder('i')
            ->update()
            ->set('i.tax', 'NULL')
            ->where('i.tax = :tax')
            ->setParameter('tax', $tax);

        $qb->getQuery()->execute();
    }
}
