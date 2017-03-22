<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class ChainFilter implements FilterInterface
{
    /**
     * @var FilterInterface[]
     */
    private $filters = [];

    /**
     * {@inheritdoc}
     */
    public function filter(Request $request, QueryBuilder $queryBuilder)
    {
        foreach ($this->filters as $filter) {
            $filter->filter($request, $queryBuilder);
        }
    }

    /**
     * @param FilterInterface $filter
     */
    public function addFilter(FilterInterface $filter)
    {
        $this->filters[] = $filter;
    }
}
