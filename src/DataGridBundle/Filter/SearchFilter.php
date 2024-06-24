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

final class SearchFilter implements FilterInterface
{
    public function __construct(
        private readonly string $query,
        private readonly array $searchFields
    ) {
    }

    public function filter(QueryBuilder $queryBuilder, array $params = []): void
    {
        if (! $this->query) {
            return;
        }

        $expr = $queryBuilder->expr();

        $fields = array_map(
            static function ($field): string {
                $alias = ORMSource::ALIAS;
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
