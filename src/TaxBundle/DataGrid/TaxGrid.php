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

namespace SolidInvoice\TaxBundle\DataGrid;

use Override;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\TaxBundle\Entity\Tax;
use SolidInvoice\TaxBundle\Enum\TaxCategory;
use SolidInvoice\TaxBundle\Enum\TaxType;
use SolidInvoice\TaxBundle\Repository\TaxRepository;

#[AsDataGrid(name: 'tax_grid', title: 'Tax Rates')]
final class TaxGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Tax::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('name')
                ->label('label.name'),
            StringColumn::new('rate')
                ->label('tax.grid.rate')
                ->formatValue(static fn (string | float $value) => (string) $value . '%'),
            StringColumn::new('type')
                ->label('tax.grid.type')
                ->formatValue(static fn (?string $value) => TaxType::tryFrom((string) $value) ?? $value),
            StringColumn::new('category')
                ->label('tax.grid.category')
                ->filter(ChoiceFilter::new('category', array_column(array_map(static fn (TaxCategory $c) => [$c->value, $c->getLabel()], TaxCategory::cases()), 1, 0))->multiple()),
            DateTimeColumn::new('created')
                ->label('label.created')
                ->format('d F Y'),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        return [
            BatchAction::new('action.delete')
                ->icon('trash')
                ->color('danger')
                ->confirmMessage('tax.grid.delete_confirm')
                ->action(static function (TaxRepository $repository, array $selectedItems): void {
                    $repository->deleteTaxRates($selectedItems);
                }),
        ];
    }

    /**
     * @return EditAction[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            EditAction::new('_tax_rates_edit', ['id' => 'id']),
        ];
    }

    public function getCreateRoute(): ?string
    {
        return '_tax_rates_add';
    }
}
