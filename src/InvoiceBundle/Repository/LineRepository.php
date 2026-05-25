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

use Brick\Math\Exception\MathException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Billing\TotalCalculator;
use SolidInvoice\InvoiceBundle\Entity\Line;
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
     * Recalculates invoice totals after a Tax rate is deleted. LineTax rows
     * retain their snapshots; the FK is auto-nulled by ON DELETE SET NULL.
     *
     * Distinct-by-invoice so a multi-tax line (or several lines on the same
     * invoice sharing the rate) only triggers a single recalculation per
     * invoice instead of one per LineTax row.
     *
     * @throws MathException
     */
    public function removeTax(Tax $tax): void
    {
        $em = $this->getEntityManager();

        $query = $em->createQueryBuilder()
            ->select('DISTINCT i')
            ->from(LineTax::class, 'lt')
            ->join('lt.invoiceLine', 'l')
            ->join('l.invoice', 'i')
            ->where('lt.tax = :tax')
            ->setParameter('tax', $tax->getId(), UlidType::NAME)
            ->getQuery();

        foreach ($query->toIterable() as $invoice) {
            $invoice->setTax(0);
            $this->calculator->calculateTotals($invoice);

            $em->persist($invoice);
        }

        $em->flush();
    }
}
