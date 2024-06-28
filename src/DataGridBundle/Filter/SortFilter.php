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

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use function str_contains;

final class SortFilter implements FilterInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string $direction = Criteria::ASC,
    ) {
    }

    public function filter(QueryBuilder $queryBuilder, mixed $value): void
    {
        if ($this->field) {
            if (str_contains($this->field, '.')) {
                $relation = explode('.', $this->field);

                $queryBuilder->join(ORMSource::ALIAS . '.' . $relation[0], $relation[0]);
                $queryBuilder->orderBy($relation[0] . '.' . $relation[1], $this->direction);
            } else {
                $queryBuilder->orderBy(ORMSource::ALIAS . '.' . $this->field, $this->direction);
            }
        }
    }
}
