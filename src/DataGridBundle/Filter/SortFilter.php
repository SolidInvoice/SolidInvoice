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

class SortFilter implements FilterInterface
{
    public function __construct(
        private readonly string $field,
        private readonly string $direction = Criteria::ASC,
    ) {
    }

    public function filter(QueryBuilder $queryBuilder): void
    {
        if ($this->field) {
            $alias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->orderBy($alias . '.' . $this->field, $this->direction);
        }
    }
}
