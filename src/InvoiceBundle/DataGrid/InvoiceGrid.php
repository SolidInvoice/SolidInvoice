<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\DataGrid;

use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;

#[AsDataGrid(name: 'invoice_grid', title: 'Active Invoices')]
final class InvoiceGrid extends BaseInvoiceGrid
{
    public function batchActions(): iterable
    {
        yield from parent::batchActions();

        yield BatchAction::new('Archive')
            ->icon('trash')
            ->color('warning')
            ->action(static function (InvoiceRepository $repository, array $selectedItems): void {
                $repository->archiveInvoices($selectedItems);
            });
    }
}
