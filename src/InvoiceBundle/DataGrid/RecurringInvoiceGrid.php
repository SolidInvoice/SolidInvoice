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

use Brick\Math\BigNumber;
use Lorisleiva\CronTranslator\CronTranslator;
use Money\Money;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Model\Graph;

#[AsDataGrid(name: self::GRID_NAME)]
final class RecurringInvoiceGrid extends Grid
{
    final public const GRID_NAME = 'recurring_invoice_grid';

    public function entityFQCN(): string
    {
        return RecurringInvoice::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('client'),
            StringColumn::new('frequency')
                ->formatValue(static fn (string $format): string => CronTranslator::translate($format)),
            DateTimeColumn::new('dateStart')
                ->format('d F Y')
                ->filter(new DateRangeFilter('dateStart')),
            DateTimeColumn::new('dateEnd')
                ->format('d F Y')
                ->filter(new DateRangeFilter('dateEnd')),
            StringColumn::new('status')
                ->twigFunction('invoice_label')
                ->filter(ChoiceFilter::new('status', Graph::statusArray())->multiple()),
            MoneyColumn::new('total')
                ->formatValue(fn (float|BigNumber $value, RecurringInvoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
            MoneyColumn::new('tax')
                ->formatValue(fn (float|BigNumber $value, RecurringInvoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
            MoneyColumn::new('discount.value')
                ->label('Discount')
                ->formatValue(fn (float|BigNumber $value, RecurringInvoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
        ];
    }

    public function actions(): array
    {
        return [
            ViewAction::new('_invoices_view_recurring', ['id' => 'id']),
            EditAction::new('_invoices_edit', ['id' => 'id']),
        ];
    }
}
