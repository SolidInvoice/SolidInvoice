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

namespace SolidInvoice\ProductBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;
use function mb_strtolower;

/**
 * @extends EntityRepository<Product>
 * @see \SolidInvoice\ProductBundle\Tests\Repository\ProductRepositoryTest
 */
final class ProductRepository extends EntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CompanySelector $companySelector,
    ) {
        parent::__construct($registry, Product::class);
    }

    /**
     * SQL filters do not apply to DQL DELETE, so the company is constrained explicitly.
     *
     * @param list<Ulid|string> $ids
     */
    public function deleteProducts(array $ids): void
    {
        // One statement per id: Doctrine cannot bind an array of ULIDs to the ulid type.
        foreach ($ids as $id) {
            $this->createQueryBuilder('p')
                ->delete()
                ->where('p.id = :id')
                ->andWhere('p.company = :company')
                ->setParameter('id', $id instanceof Ulid ? $id : Ulid::fromString($id), UlidType::NAME)
                ->setParameter('company', $this->companySelector->getCompany(), UlidType::NAME)
                ->getQuery()
                ->execute();
        }
    }

    /**
     * @return list<Product>
     */
    public function search(string $term, int $limit = 10): array
    {
        return $this->searchQueryBuilder($term)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Products whose name or description contains $term, by name.
     */
    public function searchQueryBuilder(string $term): QueryBuilder
    {
        return $this->createQueryBuilder('p')
            // LOWER on both sides: LIKE is case-sensitive on PostgreSQL.
            ->where('LOWER(p.name) LIKE :term OR LOWER(p.description) LIKE :term')
            ->setParameter('term', '%' . mb_strtolower($term) . '%')
            ->orderBy('p.name', 'ASC');
    }
}
