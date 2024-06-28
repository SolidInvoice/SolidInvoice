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

namespace SolidInvoice\TaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Line as InvoiceItem;
use SolidInvoice\QuoteBundle\Entity\Line as QuoteItem;
use SolidInvoice\TaxBundle\Entity\Tax;

class TaxRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tax::class);
    }

    /**
     * Gets an array of all the available tax rates.
     */
    public function getTaxList(): array
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select(
                [
                    't.name',
                    't.rate',
                    't.type',
                ]
            );

        $query = $queryBuilder->getQuery();

        $query->useQueryCache(true)
            ->enableResultCache((60 * 60 * 24), 'tax_list');

        return $query->getArrayResult();
    }

    public function taxRatesConfigured(): bool
    {
        return $this->count([]) > 0;
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('t');

        return $qb;
    }

    /**
     * @param list<int> $data
     */
    public function deleteTaxRates(array $data): void
    {
        $em = $this->getEntityManager();

        $invoiceRepository = $em->getRepository(InvoiceItem::class);
        $quoteRepository = $em->getRepository(QuoteItem::class);

        foreach ($data as $taxId) {
            $tax = $this->find($taxId);

            if (! $tax instanceof Tax) {
                continue;
            }

            $invoiceRepository->removeTax($tax);
            $quoteRepository->removeTax($tax);

            $em->remove($tax);
        }

        $em->flush();
    }
}
