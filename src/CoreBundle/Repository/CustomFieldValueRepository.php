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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<CustomFieldValue>
 */
class CustomFieldValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomFieldValue::class);
    }

    /**
     * @return list<CustomFieldValue>
     */
    public function findForRecord(CustomFieldTarget $target, Ulid $targetId): array
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.target = :target')
            ->andWhere('v.targetId = :targetId')
            ->setParameter('target', $target->value)
            ->setParameter('targetId', $targetId, UlidType::NAME)
            ->getQuery()
            ->getResult();
    }

    public function findOneFor(CustomField $field, Ulid $targetId): ?CustomFieldValue
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.field = :field')
            ->andWhere('v.targetId = :targetId')
            ->setParameter('field', $field->getId(), UlidType::NAME)
            ->setParameter('targetId', $targetId, UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByField(CustomField $field): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->andWhere('v.field = :field')
            ->setParameter('field', $field->getId(), UlidType::NAME)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteForRecord(CustomFieldTarget $target, Ulid $targetId): void
    {
        $this->createQueryBuilder('v')
            ->delete()
            ->andWhere('v.target = :target')
            ->andWhere('v.targetId = :targetId')
            ->setParameter('target', $target->value)
            ->setParameter('targetId', $targetId, UlidType::NAME)
            ->getQuery()
            ->execute();
    }
}
