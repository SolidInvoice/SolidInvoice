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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\ProductBundle\Entity\Product;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

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
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :term OR p.description LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->orderBy('p.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
