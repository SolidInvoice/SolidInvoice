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

namespace SolidInvoice\InvoiceBundle\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Override;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\EntityFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Manager\ReceiptManager;
use SolidInvoice\PaymentBundle\Entity\Payment;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Enum\PaymentStatus;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Translation\TranslatableMessage;
use function array_key_exists;

/**
 * Every captured payment has a receipt, so this is the payments grid narrowed to those.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\PaymentReceiptTest
 */
#[AsDataGrid(name: 'receipts_grid', title: 'Receipts')]
final class ReceiptsGrid extends Grid
{
    public function __construct(
        private readonly ReceiptManager $receiptManager,
        private readonly EmailVerificationGateInterface $emailVerificationGate,
    ) {
    }

    public function entityFQCN(): string
    {
        return Payment::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            DateTimeColumn::new('completed')
                ->label('payment.receipt.payment_date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('completed')),
            StringColumn::new('invoice')
                ->label('payment.list.grid.invoice_number')
                ->searchable(false)
                ->formatValue(static fn (Invoice $invoice): string => $invoice->getInvoiceId())
                ->linkToRoute('_invoices_view', ['id' => 'invoice.id']),
            StringColumn::new('client')
                ->label('label.client')
                ->searchable(false)
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            StringColumn::new('method')
                ->label('payment.list.grid.method')
                ->searchable(false)
                ->filter(EntityFilter::new(PaymentMethod::class, 'method', 'name')->multiple()),
            StringColumn::new('reference')
                ->label('payment.reference.label'),
            MoneyColumn::new('amount')
                ->label('payment.list.grid.amount')
                ->searchable(false)
                ->sortableField('totalAmount'),
        ];
    }

    /**
     * @return list<Action>
     */
    #[Override]
    public function actions(): array
    {
        return [
            ViewAction::new('_invoices_payment_view', ['id' => 'id']),
            Action::new('_invoices_receipt_download', ['id' => 'id'])
                ->label('payment.receipt.action.download')
                ->icon('download'),
            Action::new('_invoices_receipt_view', ['id' => 'id'])
                ->label('payment.receipt.action.view')
                ->icon('file-type-pdf')
                ->inMenu(),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        // Sending to clients is held back until the user's own address is verified.
        if ($this->emailVerificationGate->isGated()) {
            return;
        }

        yield BatchAction::new('payment.receipt.action.send')
            ->icon('send')
            ->confirmMessage('payment.receipt.confirm_send')
            ->action(function (EntityRepository $repository, array $selectedItems): void {
                foreach ($selectedItems as $id) {
                    $payment = $repository->find($id);

                    if ($payment instanceof Payment) {
                        $this->receiptManager->send($payment);
                    }
                }
            });
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $queryBuilder = $query->getQueryBuilder()
            ->andWhere(ORMSource::ALIAS . '.status = :status')
            ->andWhere(ORMSource::ALIAS . '.invoice IS NOT NULL')
            ->setParameter('status', PaymentStatus::Captured->value)
            ->orderBy(ORMSource::ALIAS . '.completed', 'DESC');

        if (array_key_exists('client_id', $this->context) && null !== $this->context['client_id']) {
            $queryBuilder
                ->andWhere(ORMSource::ALIAS . '.client = :client_id')
                ->setParameter('client_id', $this->context['client_id'], UlidType::NAME);
        }

        return $query;
    }

    #[Override]
    public function getEmptyTitle(): TranslatableMessage
    {
        return new TranslatableMessage('payment.receipt.empty.title');
    }

    #[Override]
    public function getEmptyDescription(): TranslatableMessage
    {
        return new TranslatableMessage('payment.receipt.empty.description');
    }
}
