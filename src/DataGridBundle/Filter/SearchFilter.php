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
    /**
     * @var array
     */
    private $searchFields;

    /**
     * SearchFilter constructor.
     */
    public function __construct(array $searchFields)
    {
        $this->searchFields = $searchFields;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Request $request, QueryBuilder $queryBuilder)
    {
        if ($request->query->has('q')) {
            $alias = $queryBuilder->getRootAliases()[0];

            $expr = $queryBuilder->expr();

            $fields = array_map(
                function ($field) use ($alias) {
                    if (false !== strpos($field, '.')) {
                        [$alias, $field] = explode('.', $field);
                    }

                    return sprintf('%s.%s LIKE :q', $alias, $field);
                },
                $this->searchFields
            );

            $queryBuilder->andWhere(call_user_func_array(function ($x = null) use ($expr) : Orx {
                return $expr->orX($x);
            }, $fields));
            $queryBuilder->setParameter('q', '%'.$request->query->get('q').'%');
        }
    }
}
