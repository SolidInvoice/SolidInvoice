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
use function assert;
use function is_array;
use function sprintf;
use function Symfony\Component\String\u;

final class DateRangeFilter implements ColumnFilterInterface
{
    public function __construct(
        private readonly string $field,
    ) {
    }

    public function form(): string
    {
        return DateRangeFormType::class;
    }

    public function formOptions(): array
    {
        return ['field_name' => u($this->field)->snake()->replace('_', ' ')->title()->toString()];
    }

    public function filter(QueryBuilder $queryBuilder, mixed $value): void
    {
        assert(is_array($value));

        if (array_key_exists('start', $value) && $value['start'] !== '') {
            $queryBuilder->andWhere(sprintf('%s.%s > :start', ORMSource::ALIAS, $this->field))
                ->setParameter('start', Carbon::createFromFormat('Y-m-d', $value['start'])?->startOfDay());
        }

        if (array_key_exists('end', $value) && $value['end'] !== '') {
            $queryBuilder->andWhere(sprintf('%s.%s <= :end', ORMSource::ALIAS, $this->field))
                ->setParameter('end', Carbon::createFromFormat('Y-m-d', $value['end'])?->endOfDay());
        }
    }
}
