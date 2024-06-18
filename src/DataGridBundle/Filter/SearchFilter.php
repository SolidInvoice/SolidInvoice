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

class SearchFilter implements FilterInterface
{
    public function __construct(
        private readonly string $query,
        private readonly array $searchFields
    ) {
    }

    public function filter(QueryBuilder $queryBuilder): void
    {
        if (! $this->query) {
            return;
        }

        $alias = $queryBuilder->getRootAliases()[0];

        $expr = $queryBuilder->expr();

        $fields = array_map(
            static function ($field) use ($alias): string {
                if (str_contains($field, '.')) {
                    [$alias, $field] = explode('.', $field);
                }

                return sprintf('%s.%s LIKE :q', $alias, $field);
            },
            $this->searchFields
        );

        $queryBuilder->andWhere($expr->orX(...$fields));
        $queryBuilder->setParameter('q', '%' . $this->query . '%');
    }
}
