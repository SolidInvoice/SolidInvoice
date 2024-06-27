<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\DataGrid;

use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\PaymentBundle\Entity\Payment;

#[AsDataGrid(name: 'payments_grid')]
final class PaymentsGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Payment::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('invoice.invoiceId')
                ->label('Invoice #'),
            StringColumn::new('client.name')
                ->label('Client'),
            StringColumn::new('method'),
            StringColumn::new('status')
                ->twigFunction('payment_label'),
            DateTimeColumn::new('completed')
                ->label('Completed Date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('completed')),
            StringColumn::new('message'),
            MoneyColumn::new('amount')
                ->sortableField('totalAmount'),
            DateTimeColumn::new('created')
                ->format('d F Y')
                ->filter(new DateRangeFilter('created')),
        ];
    }
}
