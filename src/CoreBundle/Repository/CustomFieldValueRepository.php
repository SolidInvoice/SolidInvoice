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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomField;
use SolidInvoice\CoreBundle\Entity\CustomField\CustomFieldValue;
use SolidInvoice\CoreBundle\Enum\CustomFieldTarget;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<CustomFieldValue>
 */
class CustomFieldValueRepository extends EntityRepository
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

    /**
     * @param iterable<CustomField> $fields
     * @return array<string, int> map of field-id-string => count
     */
    public function countByFields(iterable $fields): array
    {
        $ids = [];
        foreach ($fields as $field) {
            $id = $field->getId();
            if ($id !== null) {
                $ids[] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        $platform = $this->getEntityManager()->getConnection()->getDatabasePlatform();
        $ulidType = Type::getType(UlidType::NAME);
        $convertedIds = array_map(
            fn (Ulid $id) => $ulidType->convertToDatabaseValue($id, $platform),
            $ids,
        );

        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.field) AS field_id, COUNT(v.id) AS total')
            ->andWhere('v.field IN (:fields)')
            ->setParameter('fields', $convertedIds, ArrayParameterType::STRING)
            ->groupBy('v.field')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row['field_id']] = (int) $row['total'];
        }

        return $counts;
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
