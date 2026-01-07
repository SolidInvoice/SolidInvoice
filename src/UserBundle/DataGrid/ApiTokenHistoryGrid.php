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

namespace SolidInvoice\UserBundle\DataGrid;

use Doctrine\ORM\EntityManagerInterface;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Translation\TranslatableMessage;

#[AsDataGrid(name: 'api_token_history_grid', title: 'Request History')]
final class ApiTokenHistoryGrid extends Grid
{
    public function entityFQCN(): string
    {
        return ApiTokenHistory::class;
    }

    public function columns(): array
    {
        return [
            DateTimeColumn::new('created')
                ->label(new TranslatableMessage('Date'))
                ->format('d M Y H:i')
                ->sortable(true)
                ->filter(new DateRangeFilter('created')),
            StringColumn::new('method')
                ->label(new TranslatableMessage('Method'))
                ->sortable(false)
                ->filter(new ChoiceFilter('method', [
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                ])),
            StringColumn::new('resource')
                ->label(new TranslatableMessage('Endpoint'))
                ->sortable(false)
                ->formatValue(static fn (?string $value) => $value ? (strlen($value) > 40 ? substr($value, 0, 40) . '...' : $value) : '-'),
            StringColumn::new('statusCode')
                ->label(new TranslatableMessage('Status'))
                ->sortable(false)
                ->formatValue(static fn ($value, ApiTokenHistory $history) => $history->getStatusCode() ?? '-')
                ->filter(new ChoiceFilter('statusCode', [
                    '2xx' => '2xx Success',
                    '3xx' => '3xx Redirect',
                    '4xx' => '4xx Client Error',
                    '5xx' => '5xx Server Error',
                ])),
            StringColumn::new('ip')
                ->label(new TranslatableMessage('IP Address'))
                ->sortable(false),
            StringColumn::new('userAgent')
                ->label(new TranslatableMessage('User Agent'))
                ->sortable(false)
                ->formatValue(static fn (?string $value) => $value ? (strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value) : '-'),
        ];
    }

    public function actions(): array
    {
        return [];
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        // Filter by token ID if provided in context
        if (isset($this->context['token_id'])) {
            $query->getQueryBuilder()
                ->andWhere('IDENTITY(' . ORMSource::ALIAS . '.token) = :token')
                ->setParameter('token', $this->context['token_id'], UlidType::NAME);
        }

        $query->getQueryBuilder()
            ->orderBy(ORMSource::ALIAS . '.created', 'DESC')
            ->setMaxResults(100);

        return $query;
    }
}
