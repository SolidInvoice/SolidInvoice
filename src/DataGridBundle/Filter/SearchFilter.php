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

namespace SolidInvoice\DataGridBundle\Filter;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use SolidInvoice\DataGridBundle\Source\ORMSource;

final class SearchFilter implements FilterInterface
{
    /**
     * Doctrine field types that are safe to use with a SQL LIKE expression.
     */
    private const STRING_LIKE_TYPES = [
        Types::STRING,
        Types::ASCII_STRING,
        Types::TEXT,
        Types::GUID,
        'citext',
        'ulid',
        'uuid',
        'uuid_binary',
        'uuid_binary_ordered_time',
    ];

    /**
     * @param string[] $searchFields
     */
    public function __construct(
        private readonly array $searchFields
    ) {
    }

    public function filter(QueryBuilder $queryBuilder, mixed $value): void
    {
        if (! $value || $this->searchFields === []) {
            return;
        }

        $rootMetadata = $this->getRootMetadata($queryBuilder);

        $expr = $queryBuilder->expr();

        $fields = [];

        foreach ($this->searchFields as $field) {
            $alias = ORMSource::ALIAS;
            $fieldName = $field;

            if (str_contains($field, '.')) {
                [$alias, $fieldName] = explode('.', $field, 2);

                if ($rootMetadata !== null && ! $this->isLikableJoinedField($queryBuilder, $rootMetadata, $alias, $fieldName)) {
                    continue;
                }

                $queryBuilder->join(ORMSource::ALIAS . '.' . $alias, $alias);
            } elseif ($rootMetadata !== null && ! $this->isLikableField($rootMetadata, $fieldName)) {
                // Skip association columns (e.g. invoice/client/method) and non-string
                // scalars (e.g. integers, decimals, datetimes) — Doctrine rejects LIKE
                // on association paths and the result would be meaningless for non-strings.
                continue;
            }

            $fields[] = sprintf('%s.%s LIKE :q', $alias, $fieldName);
        }

        if ($fields === []) {
            return;
        }

        $queryBuilder->andWhere($expr->orX(...$fields));
        $queryBuilder->setParameter('q', '%' . $value . '%');
    }

    private function getRootMetadata(QueryBuilder $queryBuilder): ?ClassMetadata
    {
        $rootEntities = $queryBuilder->getRootEntities();

        if ($rootEntities === []) {
            return null;
        }

        return $queryBuilder->getEntityManager()->getClassMetadata($rootEntities[0]);
    }

    private function isLikableField(ClassMetadata $metadata, string $field): bool
    {
        if ($metadata->hasAssociation($field)) {
            return false;
        }

        if (! $metadata->hasField($field)) {
            return false;
        }

        $type = $metadata->getTypeOfField($field);

        return is_string($type) && in_array(strtolower($type), self::STRING_LIKE_TYPES, true);
    }

    private function isLikableJoinedField(QueryBuilder $queryBuilder, ClassMetadata $rootMetadata, string $association, string $field): bool
    {
        if (! $rootMetadata->hasAssociation($association)) {
            return false;
        }

        $targetClass = $rootMetadata->getAssociationTargetClass($association);
        $targetMetadata = $queryBuilder->getEntityManager()->getClassMetadata($targetClass);

        return $this->isLikableField($targetMetadata, $field);
    }
}
