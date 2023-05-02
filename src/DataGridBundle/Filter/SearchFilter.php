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

use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class SearchFilter implements FilterInterface
{
    public function __construct(
        private readonly array $searchFields
    ) {
    }

    public function filter(Request $request, QueryBuilder $queryBuilder): void
    {
        if ($request->query->has('q')) {
            $alias = $queryBuilder->getRootAliases()[0];

            $expr = $queryBuilder->expr();

            $fields = array_map(
                function ($field) use ($alias): string {
                    if (str_contains($field, '.')) {
                        [$alias, $field] = explode('.', $field);
                    }

                    return sprintf('%s.%s LIKE :q', $alias, $field);
                },
                $this->searchFields
            );

            $queryBuilder->andWhere(call_user_func_array(fn ($x = null): Orx => $expr->orX($x), $fields));
            $queryBuilder->setParameter('q', '%' . $request->query->get('q') . '%');
        }
    }
}
