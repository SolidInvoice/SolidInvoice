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

namespace SolidInvoice\DataGridBundle\Export;

use Doctrine\ORM\QueryBuilder;
use SolidInvoice\DataGridBundle\Filter\SearchFilter;
use SolidInvoice\DataGridBundle\Filter\SortFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridInterface;
use function array_filter;
use function array_map;
use function explode;

/**
 * Applies a grid's sort, search, and per-column filter state to a QueryBuilder.
 *
 * Extracted from the DataGrid LiveComponent so the same filter pipeline can be
 * shared by the paginator path (for rendering a grid) and the export path.
 */
final class GridQueryService
{
    /**
     * @param array<string, mixed> $gridFilters
     */
    public function applyFilters(
        GridInterface $grid,
        QueryBuilder $builder,
        string $sort,
        string $search,
        array $gridFilters,
    ): void {
        // SortFilter's constructor expects (field, direction = ASC); explode on an
        // empty string yields a single empty element which SortFilter::filter()
        // already treats as a no-op. The early return keeps intent obvious and
        // avoids constructing a throw-away filter when sort is unset.
        if ($sort !== '') {
            new SortFilter(...explode(',', $sort, 2))->filter($builder, null);
        }

        $searchFields = array_filter($grid->columns(), static fn (Column $column): bool => $column->isSearchable());
        $searchFields = array_map(static fn (Column $column): string => $column->getField(), $searchFields);
        new SearchFilter($searchFields)->filter($builder, $search);

        foreach ($grid->filters() as $column => $filter) {
            $filterValue = $gridFilters[$column] ?? '';

            if ($filterValue === '' || $filterValue === []) {
                continue;
            }

            $filter->filter($builder, $filterValue);
        }
    }
}
