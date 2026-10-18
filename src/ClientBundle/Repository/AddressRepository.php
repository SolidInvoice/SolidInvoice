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

namespace SolidInvoice\ClientBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\ClientBundle\Entity\Address;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<Address>
 *
 * @see \SolidInvoice\ClientBundle\Tests\Repository\AddressRepositoryTest
 */
class AddressRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Address::class);
    }

    public function findCompanyAddress(Ulid $companyId): ?Address
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.company', 'c')
            ->andWhere('c.id = :company')
            ->andWhere('a.client IS NULL')
            ->setParameter('company', $companyId, UlidType::NAME)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
