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

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\TaxBundle\Entity\TaxIdentifier;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<TaxIdentifier>
 */
final class TaxIdentifierRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaxIdentifier::class);
    }

    /**
     * Returns company-level (non-client) tax identifiers for the given company.
     *
     * @return list<TaxIdentifier>
     */
    public function findCompanyIdentifiers(Ulid $companyId): array
    {
        return $this->createQueryBuilder('ti')
            ->andWhere('ti.client IS NULL')
            ->andWhere('ti.company = :company')
            ->setParameter('company', $companyId, UlidType::NAME)
            ->orderBy('ti.primary', 'DESC')
            ->addOrderBy('ti.label', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
