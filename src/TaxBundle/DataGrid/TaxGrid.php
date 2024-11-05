<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\TaxBundle\DataGrid;

use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Repository\TaxRepository;

#[AsDataGrid(name: 'tax_grid', title: 'Tax Rates')]
final class TaxGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Tax::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('name'),
            StringColumn::new('rate')
                ->formatValue(static fn (string $value) => $value . '%'),
            StringColumn::new('type'),
            DateTimeColumn::new('created')
                ->format('d F Y'),
        ];
    }

    public function batchActions(): iterable
    {
        return [
            BatchAction::new('Delete')
                ->icon('trash')
                ->color('danger')
                ->confirmMessage(<<<MSG
Are you sure you want to delete the selected tax rates?\n
Note, deleting a tax rate will remove it from all invoices and quotes that are using it and affect the totals.
MSG)
                ->action(static function (TaxRepository $repository, array $selectedItems): void {
                    $repository->deleteTaxRates($selectedItems);
                }),
        ];
    }

    public function actions(): array
    {
        return [
            EditAction::new('_tax_rates_edit', ['id' => 'id']),
        ];
    }
}
