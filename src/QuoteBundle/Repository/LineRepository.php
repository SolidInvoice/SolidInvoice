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
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use Symfony\Bridge\Doctrine\Types\UlidType;

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
     * Recalculates quote totals after a Tax rate is deleted. LineTax rows
     * retain their snapshots; the FK is auto-nulled by ON DELETE SET NULL.
     *
     * @throws MathException
     */
    public function removeTax(Tax $tax): void
    {
        $em = $this->getEntityManager();

        $query = $em->createQueryBuilder()
            ->select('lt')
            ->from(LineTax::class, 'lt')
            ->join('lt.quoteLine', 'l')
            ->where('lt.tax = :tax')
            ->setParameter('tax', $tax->getId(), UlidType::NAME)
            ->getQuery();

        /** @var LineTax $lineTax */
        foreach ($query->toIterable() as $lineTax) {
            $quoteLine = $lineTax->getQuoteLine();

            if ($quoteLine === null) {
                continue;
            }

            $quoteLine->getQuote()->setTax(0);
            $this->calculator->calculateTotals($quoteLine->getQuote());

            $em->persist($quoteLine);
        }

        $em->flush();
    }
}
