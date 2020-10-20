<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\TaxBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\InvoiceBundle\Entity\Item as InvoiceItem;
use SolidInvoice\QuoteBundle\Entity\Item as QuoteItem;
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
        return $this->getTotal() > 0;
    }

    /**
     * Gets an array of all the available tax rates.
     */
    public function getTotal(): int
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('t');

        return $qb;
    }

    public function deleteTaxRates(array $data): void
    {
        $em = $this->getEntityManager();

        $invoiceRepository = $em->getRepository(InvoiceItem::class);
        $quoteRepository = $em->getRepository(QuoteItem::class);

        /* @var Tax[] $taxes */
        $taxes = $this->findBy(['id' => $data]);

        foreach ($taxes as $tax) {
            $invoiceRepository->removeTax($tax);
            $quoteRepository->removeTax($tax);

            $em->remove($tax);
        }

        $em->flush();
    }
}
