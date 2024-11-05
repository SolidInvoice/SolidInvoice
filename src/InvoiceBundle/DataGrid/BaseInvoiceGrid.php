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
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Model\Graph;
use SolidInvoice\InvoiceBundle\Repository\InvoiceRepository;

abstract class BaseInvoiceGrid extends Grid
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
                ->format('d F Y')
                ->filter(new DateRangeFilter('invoiceDate')),
            StringColumn::new('client')
                ->searchable(false)
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            MoneyColumn::new('balance')
                ->formatValue(fn (BigNumber $value, Invoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
            DateTimeColumn::new('due')
                ->label('Due Date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('due')),
            DateTimeColumn::new('paidDate')
                ->format('d F Y')
                ->filter(new DateRangeFilter('paidDate')),
            StringColumn::new('status')
                ->twigFunction('invoice_label')
                ->filter(ChoiceFilter::new('status', Graph::statusArray())->multiple()),
            MoneyColumn::new('total')
                ->formatValue(fn (BigNumber $value, Invoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
            MoneyColumn::new('tax')
                ->formatValue(fn (BigNumber $value, Invoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
            MoneyColumn::new('discount.value')
                ->label('Discount')
                ->searchable(false)
                ->formatValue(fn (float | BigNumber $value, Invoice $invoice) => new Money((string) $value, $invoice->getClient()?->getCurrency())),
        ];
    }

    public function actions(): array
    {
        return [
            ViewAction::new('_invoices_view', ['id' => 'id']),
            EditAction::new('_invoices_edit', ['id' => 'id']),
        ];
    }

    public function batchActions(): iterable
    {
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->action(static function (InvoiceRepository $repository, array $selectedItems): void {
                $repository->deleteInvoices($selectedItems);
            });
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query->getQueryBuilder()->orderBy(ORMSource::ALIAS . '.invoiceDate', 'DESC');

        return $query;
    }
}
