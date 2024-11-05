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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\EntityFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Model\Status;
use function array_key_exists;

#[AsDataGrid(name: 'payments_grid', title: 'Payments')]
final class PaymentsGrid extends Grid
{
    public function entityFQCN(): string
    {
        return Payment::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('invoice')
                ->label('Invoice #')
                ->formatValue(static function (Invoice $invoice) {
                    try {
                        return $invoice->getInvoiceId();
                    } catch (EntityNotFoundException $e) {
                        return null;
                    }
                })
                ->linkToRoute('_invoices_view', ['id' => 'invoice.id']),
            StringColumn::new('client')
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            StringColumn::new('method')
                ->linkToRoute('_payment_settings_index', ['method' => 'method.gatewayName'])
                ->filter(
                    EntityFilter::new(PaymentMethod::class, 'method', 'name')
                        ->multiple()
                ),
            StringColumn::new('status')
                ->twigFunction('payment_label')
                ->filter(ChoiceFilter::new('status', Status::toArray())->multiple()),
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

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query = parent::query($entityManager, $query);

        if (array_key_exists('client_id', $this->context) && null !== $this->context['client_id']) {
            $query
                ->getQueryBuilder()
                ->where(ORMSource::ALIAS . '.client = :client_id')
                ->setParameter('client_id', $this->context['client_id'], UuidBinaryOrderedTimeType::NAME);
        }

        if (array_key_exists('invoice_id', $this->context) && null !== $this->context['invoice_id']) {
            $query
                ->getQueryBuilder()
                ->where(ORMSource::ALIAS . '.invoice = :invoice_id')
                ->setParameter('invoice_id', $this->context['invoice_id'], UuidBinaryOrderedTimeType::NAME);
        }

        return $query;
    }
}
