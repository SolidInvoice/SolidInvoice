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

use Doctrine\ORM\QueryBuilder;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SortDirection;
use function str_contains;

/**
 * @see \SolidInvoice\DataGridBundle\Tests\Filter\SortFilterTest
 */
final readonly class SortFilter implements FilterInterface
{
    public function __construct(
        private string $field,
        private string $direction = 'ASC',
    ) {
    }

    public function filter(QueryBuilder $queryBuilder, mixed $value): void
    {
        $sortDirection = match ($this->direction) {
            'ASC' => SortDirection::Ascending,
            'DESC' => SortDirection::Descending,
            default => $this->direction,
        };

        if ($this->field !== '' && $this->field !== '0') {
            if (str_contains($this->field, '.')) {
                $relation = explode('.', $this->field);

                $queryBuilder->join(ORMSource::ALIAS . '.' . $relation[0], $relation[0]);
                $queryBuilder->orderBy($relation[0] . '.' . $relation[1], $sortDirection);
            } else {
                $queryBuilder->orderBy(ORMSource::ALIAS . '.' . $this->field, $sortDirection);
            }
        }
    }
}
