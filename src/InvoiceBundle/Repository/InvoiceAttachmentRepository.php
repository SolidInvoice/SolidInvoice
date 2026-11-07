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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<InvoiceAttachment>
 */
final class InvoiceAttachmentRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceAttachment::class);
    }

    /**
     * @return list<InvoiceAttachment>
     */
    public function findForInvoice(Invoice $invoice): array
    {
        /** @var list<InvoiceAttachment> $result */
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.invoice = :invoice')
            ->setParameter('invoice', $invoice)
            ->orderBy('a.created', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * @return list<InvoiceAttachment>
     */
    public function findSharedForInvoice(Invoice $invoice): array
    {
        /** @var list<InvoiceAttachment> $result */
        $result = $this->createQueryBuilder('a')
            ->andWhere('a.invoice = :invoice')
            ->andWhere('a.sharedWithClient = true')
            ->setParameter('invoice', $invoice)
            ->orderBy('a.created', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function countForInvoice(Invoice $invoice): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.invoice = :invoice')
            ->setParameter('invoice', $invoice)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
