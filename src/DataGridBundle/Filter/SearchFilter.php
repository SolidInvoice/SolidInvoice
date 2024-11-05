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
    /**
     * @param string[] $searchFields
     */
    public function __construct(
        private readonly array $searchFields
    ) {
    }

    public function filter(QueryBuilder $queryBuilder, mixed $value): void
    {
        if (! $value || $this->searchFields === []) {
            return;
        }

        $expr = $queryBuilder->expr();

        $fields = array_map(
            static function ($field) use ($queryBuilder): string {
                $alias = ORMSource::ALIAS;
                if (str_contains($field, '.')) {
                    [$alias, $field] = explode('.', $field);

                    $queryBuilder->join(ORMSource::ALIAS . '.' . $alias, $alias);
                }

                return sprintf('%s.%s LIKE :q', $alias, $field);
            },
            $this->searchFields
        );

        $queryBuilder->andWhere($expr->orX(...$fields));
        $queryBuilder->setParameter('q', '%' . $value . '%');
    }
}
