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

class ChainFilter implements FilterInterface
{
    /**
     * @var FilterInterface[]
     */
    private array $filters = [];

    public function filter(QueryBuilder $queryBuilder): void
    {
        foreach ($this->filters as $filter) {
            $filter->filter($queryBuilder);
        }
    }

    public function addFilter(FilterInterface $filter): void
    {
        $this->filters[] = $filter;
    }
}
