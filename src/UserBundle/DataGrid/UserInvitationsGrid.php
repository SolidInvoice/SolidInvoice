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

use Override;
use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Batch\BatchAction;
use SolidInvoice\DataGridBundle\GridBuilder\Column\Column;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StatusColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\InvitationStatus;
use SolidInvoice\UserBundle\Repository\UserInvitationRepository;

#[AsDataGrid(name: 'user_invitations', title: 'User Invitations')]
final class UserInvitationsGrid extends Grid
{
    public function entityFQCN(): string
    {
        return UserInvitation::class;
    }

    /**
     * @return Column[]
     */
    #[Override]
    public function columns(): array
    {
        return [
            StringColumn::new('email')
                ->label('Email Address'),
            RelativeDateColumn::new('created')
                ->label('Invited'),
            RelativeDateColumn::new('expiresAt')
                ->label('Expires'),
            StatusColumn::new('status')
                ->label('Status')
                ->statusMap([
                    InvitationStatus::Pending->value => 'warning',
                    InvitationStatus::Expired->value => 'danger',
                ])
                ->formatValue(static fn (mixed $value, UserInvitation $invitation): string => $invitation->isExpired()
                    ? InvitationStatus::Expired->value
                    : $invitation->getStatus()->value),
            StringColumn::new('invitedBy.email')
                ->label('Invited By'),
        ];
    }

    /**
     * @return Action[]
     */
    #[Override]
    public function actions(): array
    {
        return [
            Action::new('_user_resend_invite', ['id' => 'id'])
                ->label('Resend Invitation')
                ->icon('mail'),
            Action::new('_user_delete_invite', ['id' => 'id'])
                ->label('Delete')
                ->icon('trash')
                ->color('danger')
                ->confirm('Are you sure you want to delete this invitation?'),
        ];
    }

    #[Override]
    public function batchActions(): iterable
    {
        yield BatchAction::new('Delete')
            ->icon('trash')
            ->color('danger')
            ->confirm()
            ->confirmMessage('Are you sure you want to delete the selected invitation(s)?')
            ->action(static function (UserInvitationRepository $repository, array $selectedItems): void {
                $repository->deleteInvitations($selectedItems);
            });
    }
}
