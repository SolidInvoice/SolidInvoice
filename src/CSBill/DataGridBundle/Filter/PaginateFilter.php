<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class PaginateFilter implements FilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(Request $request, QueryBuilder $queryBuilder)
    {
	if ($request->query->has('per_page') && $request->query->has('page')) {
	    $queryBuilder->setMaxResults($request->query->get('per_page'));
	    $queryBuilder->setFirstResult(($request->query->get('page') - 1) * $request->query->get('per_page'));
	}
    }
}
