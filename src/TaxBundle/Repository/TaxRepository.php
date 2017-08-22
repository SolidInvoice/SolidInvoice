<?php

declare(strict_types = 1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\TaxBundle\Repository;

use SolidInvoice\TaxBundle\Entity\Tax;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

class TaxRepository extends EntityRepository
{
    /**
     * Gets an array of all the available tax rates.
     *
     * @return array
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
            ->useResultCache(true, (60 * 60 * 24), 'tax_list');

        return $query->getArrayResult();
    }

    /**
     * @return bool
     */
    public function taxRatesConfigured(): bool
    {
        return $this->getTotal() > 0;
    }

    /**
     * Gets an array of all the available tax rates.
     *
     * @return int
     */
    public function getTotal(): int
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }

    /**
     * @return QueryBuilder
     */
    public function getGridQuery(): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t');

        $qb->select('t');

        return $qb;
    }

    /**
     * @param array $data
     */
    public function deleteTaxRates(array $data): void
    {
        $em = $this->getEntityManager();

        $invoiceRepository = $em->getRepository('SolidInvoiceInvoiceBundle:Item');
        $quoteRepository = $em->getRepository('SolidInvoiceQuoteBundle:Item');

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
