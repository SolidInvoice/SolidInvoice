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

namespace SolidInvoice\CoreBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<CustomField>
 */
class CustomFieldRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomField::class);
    }

    /**
     * @return list<CustomField>
     */
    public function findByTargetOrdered(CustomFieldTarget $target): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.target = :target')
            ->setParameter('target', $target->value)
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CustomField>
     */
    public function findByTargetAndCompany(CustomFieldTarget $target, Ulid $companyId): array
    {
        return $this->createQueryBuilder('f')
            ->innerJoin('f.company', 'c')
            ->andWhere('f.target = :target')
            ->andWhere('c.id = :company')
            ->setParameter('target', $target->value)
            ->setParameter('company', $companyId, UlidType::NAME)
            ->orderBy('f.position', 'ASC')
            ->addOrderBy('f.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function nextPosition(CustomFieldTarget $target): int
    {
        $max = (int) $this->createQueryBuilder('f')
            ->select('COALESCE(MAX(f.position), -1)')
            ->andWhere('f.target = :target')
            ->setParameter('target', $target->value)
            ->getQuery()
            ->getSingleScalarResult();

        return $max + 1;
    }

    public function findOneByTargetAndKey(CustomFieldTarget $target, string $fieldKey): ?CustomField
    {
        return $this->findOneBy(['target' => $target->value, 'fieldKey' => $fieldKey]);
    }
}
