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

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;

abstract class Grid implements GridInterface
{
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
                    'DateTimeInterface', 'DateTime', 'DateTimeImmutable' => DateTimeColumn::new($property->getName()),
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

    public function batchActions(): array
    {
        return [];
    }

    /**
     * @return iterable<string, ColumnFilterInterface|null>
     */
    public function filters(): iterable
    {
        foreach ($this->columns() as $column) {
            if (null !== $column->getFilter()) {
                yield $column->getField() => $column->getFilter();
            }
        }
    }
}
