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
use Brick\Math\BigNumber;
use Brick\Math\Exception\MathException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\CreditNote;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Enum\CreditNoteStatus;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<CreditNote>
 * @see \SolidInvoice\InvoiceBundle\Tests\Repository\CreditNoteRepositoryTest
 */
class CreditNoteRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditNote::class);
    }

    /**
     * Sum of non-cancelled credit notes issued against an invoice. Excludes cancelled in SQL,
     * since {@see \SolidInvoice\CoreBundle\Billing\TotalCalculator} calls this on every totals
     * recalculation — see `design` §5 on SOL-69.
     *
     * $excluding leaves one specific credit note out of the sum regardless of its own persisted
     * status. {@see \SolidInvoice\CoreBundle\Billing\TotalCalculator::calculateOverflowContribution()}
     * and the over-crediting cap validator both use it to isolate one credit note's own
     * contribution from the rest, independent of flush ordering.
     *
     * @throws MathException
     */
    public function getTotalCreditedForInvoice(Invoice $invoice, ?CreditNote $excluding = null): BigNumber
    {
        if (! $invoice->getId() instanceof Ulid) {
            return BigInteger::zero();
        }

        $qb = $this->createQueryBuilder('cn');

        $qb->select('SUM(cn.total)')
            ->where('cn.invoice = :invoice')
            ->andWhere('cn.status != :cancelled')
            ->setParameter('invoice', $invoice->getId(), UlidType::NAME)
            ->setParameter('cancelled', CreditNoteStatus::Cancelled);

        if ($excluding instanceof CreditNote && $excluding->getId() instanceof Ulid) {
            $qb->andWhere('cn.id != :excludedId')
                ->setParameter('excludedId', $excluding->getId(), UlidType::NAME);
        }

        try {
            $result = $qb->getQuery()->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException) {
            return BigInteger::zero();
        }

        // SUM() over zero matching rows still returns exactly one row, with NULL as the sum.
        if ($result === null) {
            return BigInteger::zero();
        }

        return BigNumber::of($result);
    }
}
