<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Repository;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\TaxBundle\Entity\Tax;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Line::class);
    }

    /**
     * Removes all tax rates from invoices.
     * @throws MathException
     */
    public function removeTax(Tax $tax): void
    {
        if (Tax::TYPE_EXCLUSIVE === $tax->getType()) {
            $qb = $this->createQueryBuilder('i');

            $query = $qb->where('i.tax = :tax')
                ->setParameter('tax', $tax)
                ->groupBy('i.invoice')
                ->getQuery();

            /** @var Invoice $invoice */
            foreach ($query->execute() as $invoice) {
                $invoice->setTotal($invoice->getBaseTotal()->toBigDecimal()->plus($invoice->getTax()));
                $invoice->setTax(BigInteger::zero());
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
