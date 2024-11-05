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

namespace SolidInvoice\DataGridBundle;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Query;

abstract class Grid implements GridInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $context = [];

    /**
     * @param array<string, mixed> $context
     */
    public function initialize(array $context): void
    {
        $this->context = $context;
    }

    /**
     * @return list<Column>
     * @throws ReflectionException
     */
    public function columns(): array
    {
        $columns = [];

        foreach ((new ReflectionClass($this->entityFQCN()))->getProperties() as $property) {
            $type = $property->hasType() ? $property->getType() : null;

            if ($type instanceof ReflectionNamedType) {
                $columns[] = match ($type->getName()) {
                    DateTimeInterface::class, DateTime::class, DateTimeImmutable::class => DateTimeColumn::new($property->getName()),
                    default => StringColumn::new($property->getName()),
                };
            } else {
                $columns[] = StringColumn::new($property->getName());
            }
        }

        return $columns;
    }

    /**
     * @return list<Action>
     */
    public function actions(): array
    {
        return [];
    }

    public function batchActions(): iterable
    {
        return [];
    }

    /**
     * @return iterable<string, ColumnFilterInterface|null>
     * @throws ReflectionException
     */
    public function filters(): iterable
    {
        foreach ($this->columns() as $column) {
            if ($column->getFilter() instanceof ColumnFilterInterface) {
                yield $column->getField() => $column->getFilter();
            }
        }
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        return $query;
    }
}
