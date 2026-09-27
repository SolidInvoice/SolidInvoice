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

use Brick\Math\BigNumber;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Money\Money;
use Override;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Doctrine\Filter\ArchivableFilter;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Action\ViewAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Enum\RecurringInvoiceStatus;
use SolidInvoice\InvoiceBundle\Recurring\RecurringSchedule;
use SolidInvoice\InvoiceBundle\Repository\RecurringInvoiceRepository;
use SolidInvoice\MoneyBundle\Calculator;

abstract class BaseRecurringInvoiceGrid extends Grid
{
    public function __construct(
        protected readonly RecurringSchedule $schedule,
        protected readonly Calculator $calculator,
    ) {
    }

    public function entityFQCN(): string
    {
        return RecurringInvoice::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('client')
                ->searchable(false)
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            StringColumn::new('frequency')
                ->formatValue(fn (RecurringInvoice $recurringInvoice): string => $this->schedule->getFrequency($recurringInvoice->getRecurringOptions())),
            DateTimeColumn::new('dateStart')
                ->format('d F Y')
                ->filter(new DateRangeFilter('dateStart')),
            DateTimeColumn::new('endDate')
                ->format('d F Y')
                ->formatValue(fn (RecurringInvoice $recurringInvoice) => $this->schedule->getEndDate($recurringInvoice->getRecurringOptions()))
                ->filter(new DateRangeFilter('endDate')),
            DateTimeColumn::new('nextRunDate')
                ->label('Next Run Date')
                ->formatValue(fn (RecurringInvoice $recurringInvoice): ?DateTimeInterface => $this->schedule->getNextRunDate($recurringInvoice->getRecurringOptions()))
                ->format('d F Y'),
            StringColumn::new('status')
                ->twigFunction('invoice_label')
                ->filter(ChoiceFilter::new('status', array_column(array_map(static fn (RecurringInvoiceStatus $s) => [$s->value, $s->name], RecurringInvoiceStatus::cases()), 1, 0))->multiple()),
            MoneyColumn::new('total')
                ->formatValue(function (float | BigNumber $value, RecurringInvoice $invoice): Money {
                    $client = $invoice->getClient();
                    if (! $client instanceof Client) {
                        throw new InvalidArgumentException(sprintf('RecurringInvoice #%s must have a client with currency', $invoice->getId()));
                    }

                    return new Money((string) $value, $client->getCurrency());
                }),
            MoneyColumn::new('tax')
                ->formatValue(function (float | BigNumber $value, RecurringInvoice $invoice): Money {
                    $client = $invoice->getClient();
                    if (! $client instanceof Client) {
                        throw new InvalidArgumentException(sprintf('RecurringInvoice #%s must have a client with currency', $invoice->getId()));
                    }

                    return new Money((string) $value, $client->getCurrency());
                }),
            MoneyColumn::new('payableAmount')
                ->label('Payable')
                ->searchable(false)
                ->formatValue(function (BigNumber $value, RecurringInvoice $invoice): Money {
                    $client = $invoice->getClient();
                    if (! $client instanceof Client) {
                        throw new InvalidArgumentException(sprintf('RecurringInvoice #%s must have a client with currency', $invoice->getId()));
                    }

                    $withholding = $invoice->getWithholdingAmount();
                    $amount = $withholding->isPositive() ? $value : $invoice->getTotal();

                    return new Money((string) $amount, $client->getCurrency());
                }),
            MoneyColumn::new('discount.value')
                ->label('Discount')
                ->searchable(false)
                ->formatValue(function (float | BigNumber $value, RecurringInvoice $invoice): Money {
                    $discountAmount = $this->calculator->calculateDiscount($invoice);

                    return new Money((string) $discountAmount, $invoice->getClient()?->getCurrency());
                }),
        ];
    }

    /**
     * @return Action[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            ViewAction::new('_invoices_view_recurring', ['id' => 'id']),
            EditAction::new('_invoices_edit_recurring', ['id' => 'id']),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->action(static function (RecurringInvoiceRepository $repository, array $selectedItems): void {
                $repository->deleteInvoices($selectedItems);
            });
    }

    public function getCreateRoute(): ?string
    {
        return '_invoices_create_recurring';
    }

    /**
     * Joins the client eagerly so the column and money formatters above
     * never touch a lazy proxy: without this, an archived client throws
     * EntityNotFoundException the moment a row is rendered. A left join
     * keeps rows whose client is genuinely null (recurring invoices allow
     * that), rather than dropping them.
     */
    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query->getQueryBuilder()
            ->select(ORMSource::ALIAS, 'client')
            ->leftJoin(ORMSource::ALIAS . '.client', 'client');

        return ArchivableFilter::suspendForAssociatedClient($entityManager, $query);
    }
}
