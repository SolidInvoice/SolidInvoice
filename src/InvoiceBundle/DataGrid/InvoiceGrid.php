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
use Money\Currency;
use Money\Money;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\InvoiceBundle\Entity\Invoice;

#[AsDataGrid(name: 'invoice_grid')]
final class InvoiceGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Invoice::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('invoiceId')
                ->label('Invoice #'),
            DateTimeColumn::new('invoiceDate')
                ->format('d F Y'),
            StringColumn::new('client'),
            MoneyColumn::new('balance')
                ->formatValue(fn (BigNumber $value, Invoice $invoice) => new Money($value, new Currency($invoice->getClient()->getCurrency()))),
            DateTimeColumn::new('due')
                ->label('Due Date')
                ->format('d F Y'),
            DateTimeColumn::new('paidDate')
                ->format('d F Y'),
            StringColumn::new('status')
                ->twigFunction('invoice_label'),
            MoneyColumn::new('total'),
            MoneyColumn::new('tax'),
            MoneyColumn::new('discount.value')
                ->label('Discount')
                ->formatValue(fn (float|BigNumber $value, Invoice $invoice) => new Money($value, new Currency($invoice->getClient()->getCurrency()))),
        ];
    }

    public function actions(): array
    {
        return [
            ViewAction::new('_invoices_view', ['id' => 'id']),
            EditAction::new('_invoices_edit', ['id' => 'id']),
        ];
    }
}
