<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\InvoiceBundle\Repository;

use CSBill\InvoiceBundle\Entity\Invoice;
use CSBill\TaxBundle\Entity\Tax;
use Doctrine\ORM\EntityRepository;

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
