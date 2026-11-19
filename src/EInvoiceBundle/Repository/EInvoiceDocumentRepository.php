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

namespace SolidInvoice\EInvoiceBundle\Repository;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Enum\EInvoiceStatus;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends EntityRepository<EInvoiceDocument>
 * @see \SolidInvoice\EInvoiceBundle\Tests\Repository\EInvoiceDocumentRepositoryTest
 */
class EInvoiceDocumentRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EInvoiceDocument::class);
    }

    /**
     * Every document transmitted against one invoice, newest first.
     *
     * @return list<EInvoiceDocument>
     */
    public function findForInvoice(Invoice $invoice): array
    {
        /** @var list<EInvoiceDocument> */
        return $this->createQueryBuilder('d')
            ->where('d.invoice = :invoice')
            ->setParameter('invoice', $invoice->getId(), UlidType::NAME)
            ->orderBy('d.created', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Transmitted documents whose poll window has opened, for the polling scan (#2679).
     *
     * @return list<EInvoiceDocument>
     */
    public function findDueForPolling(DateTimeImmutable $now): array
    {
        /** @var list<EInvoiceDocument> */
        return $this->createQueryBuilder('d')
            ->where('d.status = :status')
            ->andWhere('d.pollAfter <= :now')
            ->setParameter('status', EInvoiceStatus::Transmitted)
            ->setParameter('now', $now)
            ->orderBy('d.pollAfter', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
