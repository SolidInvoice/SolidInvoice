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

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\EditAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\TimeTrackingBundle\Entity\TimeEntry;
use SolidInvoice\TimeTrackingBundle\Enum\TimeEntryStatus;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Translation\TranslatableMessage;

#[AsDataGrid(name: 'time_entry_grid', title: 'Time Entries')]
final class TimeEntryGrid extends Grid
{
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
                ->linkToRoute('_clients_view', ['id' => 'client.id']),
            StringColumn::new('description'),
            StringColumn::new('duration')
                ->label('Duration')
                ->formatValue(static function (int $seconds): string {
                    $hours = (int) floor($seconds / 3600);
                    $minutes = (int) floor(($seconds % 3600) / 60);

                    return sprintf('%dh %02dm', $hours, $minutes);
                }),
            StringColumn::new('status')
                ->formatValue(static fn (TimeEntryStatus $value): string => ucfirst($value->value))
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

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $query->getQueryBuilder()
            ->select(ORMSource::ALIAS, 'client', 'user')
            ->innerJoin(ORMSource::ALIAS . '.client', 'client')
            ->innerJoin(ORMSource::ALIAS . '.user', 'user')
            ->orderBy(ORMSource::ALIAS . '.date', 'DESC');

        if (isset($this->context['client_id'])) {
            $query->getQueryBuilder()
                ->where(ORMSource::ALIAS . '.client = :client_id')
                ->setParameter('client_id', $this->context['client_id'], UlidType::NAME);
        }

        // Default filter: only pending (not invoiced)
        if (! isset($this->context['show_all'])) {
            $query->getQueryBuilder()
                ->andWhere(ORMSource::ALIAS . '.status = :status')
                ->setParameter('status', TimeEntryStatus::Pending->value);
        }

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
