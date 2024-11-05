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

use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\QuoteBundle\Repository\QuoteRepository;

#[AsDataGrid(name: 'quote_grid', title: 'Active Quotes')]
final class QuoteGrid extends BaseQuoteGrid
{
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Archive')
            ->icon('trash')
            ->color('warning')
            ->action(static function (QuoteRepository $repository, array $selectedItems): void {
                $repository->archiveQuotes($selectedItems);
            });
    }
}
