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

namespace SolidInvoice\TimeTrackingBundle\DataGrid;

use Brick\Math\BigDecimal;
use Brick\Math\BigNumber;
use Brick\Math\RoundingMode;
use Doctrine\ORM\EntityManagerInterface;
use Money\Money;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\MoneyColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StatusColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\EntityFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidInvoice\TimeTrackingBundle\Entity\TimeEntry;
use SolidInvoice\TimeTrackingBundle\Enum\TimeEntryStatus;
use SolidInvoice\TimeTrackingBundle\Manager\TimeEntryManager;
use SolidInvoice\TimeTrackingBundle\Repository\TimeEntryRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatableMessage;

#[AsDataGrid(name: 'time_entry_grid', title: 'Time Entries')]
final class TimeEntryGrid extends Grid
{
    public function __construct(
        private readonly TimeEntryManager $timeEntryManager,
        private readonly RouterInterface $router,
        private readonly SystemConfig $systemConfig,
    ) {
    }

    public function entityFQCN(): string
    {
        return TimeEntry::class;
    }

    public function columns(): array
    {
        return [
            DateTimeColumn::new('date')
                ->label('Date')
                ->format('d F Y')
                ->filter(new DateRangeFilter('date')),
            StringColumn::new('client')
                ->searchable(false)
                ->formatValue(static fn (?Client $client): string => $client?->getName() ?? '—')
                ->linkToRoute('_clients_view', ['id' => 'client.id'])
                ->filter(EntityFilter::new(Client::class, 'client', 'name')),
            StringColumn::new('user')
                ->label('User')
                ->formatValue(static function (User $user): string {
                    $name = trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));
                    return $name !== '' ? $name : $user->getUserIdentifier();
                }),
            StringColumn::new('description'),
            StringColumn::new('duration')
                ->label('Duration')
                ->formatValue(static function (int $seconds): string {
                    $hours = (int) floor($seconds / 3600);
                    $minutes = (int) floor(($seconds % 3600) / 60);

                    return sprintf('%dh %02dm', $hours, $minutes);
                }),
            MoneyColumn::new('hourlyRate')
                ->label('Total')
                ->sortable(false)
                ->searchable(false)
                ->formatValue(function (BigNumber $rate, TimeEntry $entry): Money {
                    $hours = BigDecimal::of($entry->getDuration())->dividedBy(3600, 6, RoundingMode::HALF_UP);
                    $totalCents = BigDecimal::of($rate)->multipliedBy($hours)->toScale(0, RoundingMode::HALF_UP);
                    $currency = $entry->getClient()?->getCurrencyCode() !== null
                        ? $entry->getClient()->getCurrency()
                        : $this->systemConfig->getCurrency();

                    return new Money((string) $totalCents, $currency);
                }),
            StatusColumn::new('status')
                ->statusMap(['pending' => 'warning', 'invoiced' => 'success'])
                ->titleCase()
                ->formatValue(static fn (TimeEntryStatus $status): string => $status->value)
                ->filter(
                    ChoiceFilter::new(
                        'status',
                        array_column(
                            array_map(static fn (TimeEntryStatus $s) => [$s->value, ucfirst($s->value)], TimeEntryStatus::cases()),
                            1,
                            0,
                        ),
                    ),
                ),
        ];
    }

    public function actions(): array
    {
        return [
            EditAction::new('_time_tracking_entry_edit', ['id' => 'id']),
        ];
    }

    public function batchActions(): iterable
    {
        yield BatchAction::new('Generate Invoice')
            ->icon('file-invoice')
            ->color('primary')
            ->confirm()
            ->confirmMessage('Generate a draft invoice for the selected time entries? All entries must belong to the same client and be in Pending status.')
            ->action(function (TimeEntryRepository $_repository, array $selectedItems): string {
                $invoice = $this->timeEntryManager->generateInvoice($selectedItems);
                return $this->router->generate('_invoices_view', ['id' => $invoice->getId()]);
            });

        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->confirm()
            ->confirmMessage('Are you sure you want to delete the selected time entries? This action cannot be undone.')
            ->action(static function (TimeEntryRepository $repository, array $selectedItems): void {
                $repository->deleteByIds($selectedItems);
            });
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query->getQueryBuilder()
            ->select(ORMSource::ALIAS, 'client', 'user')
            ->leftJoin(ORMSource::ALIAS . '.client', 'client')
            ->innerJoin(ORMSource::ALIAS . '.user', 'user')
            ->orderBy(ORMSource::ALIAS . '.date', 'DESC')
            ->addOrderBy(ORMSource::ALIAS . '.created', 'DESC');

        if (isset($this->context['client_id'])) {
            $query->getQueryBuilder()
                ->where(ORMSource::ALIAS . '.client = :client_id')
                ->setParameter('client_id', $this->context['client_id'], UlidType::NAME);
        }

        $status = $this->context['status'] ?? TimeEntryStatus::Pending->value;
        $query->getQueryBuilder()
            ->andWhere(ORMSource::ALIAS . '.status = :status')
            ->setParameter('status', $status);

        return $query;
    }

    public function getCreateRoute(): ?string
    {
        return '_time_tracking_entry_create';
    }

    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('Log Time');
    }
}
