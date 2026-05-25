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
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\TaxBundle\Entity\LineTax;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<Line>
 */
class LineRepository extends EntityRepository
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
     * Distinct-by-quote so a multi-tax line (or several lines on the same
     * quote sharing the rate) only triggers a single recalculation per quote
     * instead of one per LineTax row.
     *
     * @throws MathException
     */
    public function removeTax(Tax $tax): void
    {
        $em = $this->getEntityManager();

        $query = $em->createQueryBuilder()
            ->select('DISTINCT q')
            ->from(LineTax::class, 'lt')
            ->join('lt.quoteLine', 'l')
            ->join('l.quote', 'q')
            ->where('lt.tax = :tax')
            ->setParameter('tax', $tax->getId(), UlidType::NAME)
            ->getQuery();

        foreach ($query->toIterable() as $quote) {
            $quote->setTax(0);
            $this->calculator->calculateTotals($quote);

            $em->persist($quote);
        }

        $em->flush();
    }
}
