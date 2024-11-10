<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Filter;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use Ramsey\Uuid\Uuid;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use function array_filter;
use function array_map;
use function array_values;
use function is_string;
use function md5;
use function sprintf;
use function substr;

final class EntityFilter implements ColumnFilterInterface
{
    private bool $multiple = false;

    private function __construct(
        private readonly string $class,
        private readonly string $alias,
        private readonly string $field,
    ) {
    }

    public static function new(string $class, string $alias, string $field): self
    {
        return new self($class, $alias, $field);
    }

    public function multiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        return $this;
    }

    public function form(): string
    {
        return EntityType::class;
    }

    public function formOptions(): array
    {
        return [
            'placeholder' => 'Choose a value',
            'multiple' => $this->multiple,
            'choice_name' => $this->field,
            'choice_value' => function (object | string $entity) {
                if (is_string($entity)) {
                    return $entity;
                }
                return (string) $entity->getId();
            },
            'class' => $this->class,
        ];
    }

    public function filter(QueryBuilder $queryBuilder, mixed $value): void
    {
        $hash = substr(md5($this->class . $this->field), 0, 6);

        if ($this->multiple) {
            assert(is_array($value));

            if ([] !== $value) {
                $platform = $queryBuilder->getEntityManager()->getConnection()->getDatabasePlatform();
                $type = Type::getType(UuidBinaryOrderedTimeType::NAME);
                $parameterType = ArrayParameterType::STRING;

                $values = array_map(static function ($value) use ($type, $platform) {
                    return $type->convertToDatabaseValue($value, $platform);
                }, array_values(array_filter($value, static fn ($v) => '' !== (string) $v)));

                $queryBuilder->join(ORMSource::ALIAS . '.' . $this->alias, $hash)
                    ->andWhere(sprintf('%1$s.id IN (:%1$s)', $hash))
                    ->setParameter($hash, $values, $parameterType);
            }
        } else {
            assert(is_string($value));

            if ('' !== $value) {
                $queryBuilder->join($this->class, $hash)
                    ->andWhere(sprintf('%1$s.id = :%1$s', $hash))
                    ->setParameter($hash, Uuid::fromString($value), UuidBinaryOrderedTimeType::NAME);
            }
        }
    }
}
