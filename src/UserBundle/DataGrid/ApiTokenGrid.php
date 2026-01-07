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
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Query;
use SolidInvoice\DataGridBundle\Source\ORMSource;
use SolidInvoice\UserBundle\Entity\ApiToken;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Translation\TranslatableMessage;

#[AsDataGrid(name: 'api_token_grid', title: 'API Tokens')]
final class ApiTokenGrid extends Grid
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function entityFQCN(): string
    {
        return ApiToken::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('name')
                ->label('Name')
                ->searchable(true)
                ->sortable(true),
            StringColumn::new('description')
                ->label('Description')
                ->searchable(true)
                ->sortable(false)
                ->formatValue(static fn (?string $value) => $value ? (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) : '-'),
            StringColumn::new('usageCount')
                ->label('Usage Count')
                ->searchable(false)
                ->sortable(true)
                ->formatValue(static fn ($value, ApiToken $token) => $token->getUsageCount()),
            RelativeDateColumn::new('lastUsed')
                ->label('Last Used')
                ->searchable(false)
                ->sortable(false)
                ->formatValue(static function ($value, ApiToken $token) {
                    $history = $token->getHistory();
                    return $history->count() > 0 ? $history->first()->getCreated() : null;
                }),
            RelativeDateColumn::new('created')
                ->label('Created')
                ->sortable(true),
        ];
    }

    public function actions(): array
    {
        return [
            Action::new('_api_keys_index', ['view_history' => 'id'])
                ->label('View History')
                ->icon('history')
                ->inMenu(),
        ];
    }

    public function batchActions(): iterable
    {
        yield BatchAction::new('Revoke')
            ->icon('ban')
            ->color('danger')
            ->action(function (ApiTokenRepository $repository, array $selectedItems): void {
                $currentUser = $this->security->getUser();

                if (! $currentUser instanceof User) {
                    return;
                }

                foreach ($selectedItems as $tokenId) {
                    $token = $repository->find($tokenId);
                    if ($token instanceof ApiToken) {
                        $tokenUser = $token->getUser();
                        assert($tokenUser instanceof User);
                        if ($tokenUser->getId() === $currentUser->getId()) {
                            $repository->revoke($token);
                        }
                    }
                }
            });
    }

    public function query(EntityManagerInterface $entityManager, Query $query): Query
    {
        $user = $this->security->getUser();

        assert($user instanceof User);

        $query->getQueryBuilder()
            ->leftJoin(ORMSource::ALIAS . '.history', 'h')
            ->addSelect('h')
            ->where(ORMSource::ALIAS . '.user = :user')
            ->setParameter('user', $user->getId(), UlidType::NAME)
            ->orderBy(ORMSource::ALIAS . '.created', 'DESC');

        return $query;
    }

    public function getCreateLabel(): ?TranslatableMessage
    {
        return new TranslatableMessage('Create API Token');
    }
}
