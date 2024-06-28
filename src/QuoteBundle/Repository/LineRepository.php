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

use Brick\Math\Exception\MathException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\QuoteBundle\Entity\Line;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\TaxBundle\Entity\Tax;

/**
 * @extends ServiceEntityRepository<Line>
 */
class LineRepository extends ServiceEntityRepository
{
    public function __construct(
        private readonly TotalCalculator $calculator,
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, Line::class);
    }

    /**
     * Removes all tax rates from invoices.
     *
     * @throws MathException
     */
    public function removeTax(Tax $tax): void
    {
        $qb = $this->createQueryBuilder('i');

        $query = $qb
            ->where('i.tax = :tax')
            ->setParameter('tax', $tax->getId(), UuidBinaryOrderedTimeType::NAME)
            ->getQuery();

        $em = $this->getEntityManager();

        /** @var Item $quoteItem */
        foreach ($query->toIterable() as $quoteItem) {
            $quoteItem->setTax(null);

            $quoteItem->getQuote()->setTax(0);
            $this->calculator->calculateTotals($quoteItem->getQuote());

            $em->persist($quoteItem);
        }

        $em->flush();
    }
}
