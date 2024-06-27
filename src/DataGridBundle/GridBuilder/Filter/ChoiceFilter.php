<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\DataGridBundle\GridBuilder\Filter;

use Carbon\Carbon;
use Doctrine\ORM\QueryBuilder;
use SolidInvoice\DataGridBundle\Filter\ColumnFilterInterface;
use SolidInvoice\DataGridBundle\Form\Type\DateRangeFormType;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use function array_key_exists;

final class ChoiceFilter implements ColumnFilterInterface
{
    public function __construct(
        private readonly string $field,
    ) {
    }

    public function form(): string
    {
        return DateRangeFormType::class;
    }

    public function filter(QueryBuilder $queryBuilder, array $params = []): void
    {
        if (array_key_exists('start', $params) && $params['start'] !== '') {
            $queryBuilder->andWhere(sprintf('%s.%s > :start', ORMSource::ALIAS, $this->field))
                ->setParameter('start', Carbon::createFromFormat('Y-m-d', $params['start'])?->startOfDay());
        }

        if (array_key_exists('end', $params) && $params['end'] !== '') {
            $queryBuilder->andWhere(sprintf('%s.%s <= :end', ORMSource::ALIAS, $this->field))
                ->setParameter('end', Carbon::createFromFormat('Y-m-d', $params['end'])?->endOfDay());
        }
    }
}
