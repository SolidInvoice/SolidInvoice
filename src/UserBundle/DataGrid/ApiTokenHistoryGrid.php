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
use Override;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\ChoiceFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Filter\DateRangeFilter;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\UserBundle\Entity\ApiTokenHistory;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * @see \SolidInvoice\UserBundle\Tests\DataGrid\ApiTokenHistoryGridTest
 */
#[AsDataGrid(name: 'api_token_history_grid', title: 'Request History')]
final class ApiTokenHistoryGrid extends Grid
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function entityFQCN(): string
    {
        return ApiTokenHistory::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            DateTimeColumn::new('created')
                ->label(new TranslatableMessage('profile.api.history.list.date'))
                ->format('d M Y H:i')
                ->sortable(true)
                ->filter(new DateRangeFilter('created')),
            StringColumn::new('method')
                ->label(new TranslatableMessage('profile.api.history.list.method'))
                ->sortable(false)
                ->filter(new ChoiceFilter('method', [
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'PATCH' => 'PATCH',
                    'DELETE' => 'DELETE',
                ])),
            StringColumn::new('resource')
                ->label(new TranslatableMessage('profile.api.history.list.endpoint'))
                ->sortable(false)
                ->formatValue(static fn (?string $value) => $value ? (strlen($value) > 40 ? substr($value, 0, 40) . '...' : $value) : '-'),
            StringColumn::new('statusCode')
                ->label(new TranslatableMessage('label.status'))
                ->sortable(false)
                ->formatValue(static fn ($value, ApiTokenHistory $history) => $history->getStatusCode() ?? '-')
                ->filter(new ChoiceFilter('statusCode', [
                    '2xx' => 'profile.api.history.status_code.success',
                    '3xx' => 'profile.api.history.status_code.redirect',
                    '4xx' => 'profile.api.history.status_code.client_error',
                    '5xx' => 'profile.api.history.status_code.server_error',
                ])),
            StringColumn::new('ip')
                ->label(new TranslatableMessage('profile.api.history.list.ip'))
                ->sortable(false),
            StringColumn::new('userAgent')
                ->label(new TranslatableMessage('profile.api.history.list.user_agent'))
                ->sortable(false)
                ->formatValue(static fn (?string $value) => $value ? (strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value) : '-'),
        ];
    }

    #[Override]
    public function actions(): array
    {
        return [];
    }

    #[Override]
    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $user = $this->security->getUser();

        assert($user instanceof User);

        $queryBuilder = $query->getQueryBuilder();

        // Only ever expose history for tokens that belong to the current user
        $queryBuilder
            ->join(ORMSource::ALIAS . '.token', 'apiToken')
            ->andWhere('apiToken.user = :user')
            ->setParameter('user', $user->getId(), UlidType::NAME);

        // Filter by token ID if provided in context
        if (isset($this->context['token_id'])) {
            $queryBuilder
                ->andWhere('IDENTITY(' . ORMSource::ALIAS . '.token) = :token')
                ->setParameter('token', $this->context['token_id'], UlidType::NAME);
        }

        $queryBuilder
            ->orderBy(ORMSource::ALIAS . '.created', 'DESC')
            ->setMaxResults(100);

        return $query;
    }
}
