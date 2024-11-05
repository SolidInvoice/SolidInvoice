<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\QuoteBundle\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\CoreBundle\Doctrine\Filter\ArchivableFilter;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;

#[AsDataGrid(name: 'archived_quote_grid', title: 'Archived Quotes')]
final class ArchivedQuoteGrid extends BaseQuoteGrid
{
    public function actions(): array
    {
        return [];
    }

    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Activate')
            ->icon('refresh')
            ->color('success')
            ->action(static function (QuoteRepository $repository, array $selectedItems): void {
                $repository->restoreQuotes($selectedItems);
            });
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        return ArchivableFilter::disableForGrid($entityManager, parent::query($entityManager, $query));
    }
}
