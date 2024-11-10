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

namespace SolidInvoice\QuoteBundle\Repository;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * @extends ServiceEntityRepository<Line>
 */
class LineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Line::class);
    }

    /**
     * Removes all tax rates from invoices.
     *
     * @throws MathException
     */
    public function removeTax(Tax $tax): void
    {
        if (Tax::TYPE_EXCLUSIVE === $tax->getType()) {
            $qb = $this->createQueryBuilder('i');

            $query = $qb->where('i.tax = :tax')
                ->setParameter('tax', $tax)
                ->groupBy('i.quote')
                ->getQuery();

            /** @var Quote $quote */
            foreach ($query->execute() as $quote) {
                $baseTotal = $quote->getBaseTotal();
                assert($baseTotal instanceof BigInteger || $baseTotal instanceof BigDecimal);

                $quote->setTotal($baseTotal->plus($quote->getTax()));
                $quote->setTax(BigInteger::zero());
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
