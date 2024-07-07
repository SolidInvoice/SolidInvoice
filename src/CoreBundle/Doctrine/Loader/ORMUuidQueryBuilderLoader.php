<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Doctrine\Loader;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;
use Symfony\Component\Form\Exception\LogicException;

final class ORMUuidQueryBuilderLoader extends ORMQueryBuilderLoader
{
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
        private readonly AbstractPlatform $platform
    ) {
        parent::__construct($this->queryBuilder);
    }

    /**
     * @param mixed $identifier
     * @param list<string|UuidInterface> $values
     * @return array<object>
     * @throws Exception
     * @throws ConversionException
     */
    public function getEntitiesByIds($identifier, array $values): array
    {
        $qb = clone $this->queryBuilder;
        $alias = current($qb->getRootAliases());
        $parameter = 'ORMUuidQueryBuilderLoader_getEntitiesByIds_' . $identifier;
        $parameter = str_replace('.', '_', $parameter);
        $where = $qb->expr()->in($alias . '.' . $identifier, ':' . $parameter);

        // Guess type
        /** @var class-string<object> $entity */
        $entity = current($qb->getRootEntities());
        $metadata = $qb->getEntityManager()->getClassMetadata($entity);
        if ('uuid_binary_ordered_time' !== $metadata->getTypeOfField($identifier)) {
            throw new LogicException(sprintf(
                'ORMUuidQueryBuilderLoader supports uuid_binary_ordered_time identifiers only, %s given.',
                $metadata->getTypeOfField($identifier)
            ));
        }

        $parameterType = ArrayParameterType::STRING;

        $values = array_map(function ($value) {
            return Type::getType(UuidBinaryOrderedTimeType::NAME)->convertToDatabaseValue($value, $this->platform);
        }, array_values(array_filter($values, static fn ($v) => '' !== (string) $v)));

        if (! $values) {
            return [];
        }

        return $qb->andWhere($where)
            ->getQuery()
            ->setParameter($parameter, $values, $parameterType)
            ->getResult();
    }
}
