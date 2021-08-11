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
use Symfony\Component\HttpFoundation\Request;

class SortFilter implements FilterInterface
{
    public const DEFAULT_ORDER = 'ASC';

    /**
     * {@inheritdoc}
     */
    public function filter(Request $request, QueryBuilder $queryBuilder)
    {
        $order = $request->query->get('order') ?: self::DEFAULT_ORDER;

        if ($request->query->has('sort')) {
            $alias = $queryBuilder->getRootAliases()[0];
            $queryBuilder->orderBy($alias.'.'.$request->query->get('sort'), $order);
        }
    }
}
