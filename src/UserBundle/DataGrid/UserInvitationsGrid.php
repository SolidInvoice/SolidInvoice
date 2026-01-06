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

use SolidInvoice\DataGridBundle\Attributes\AsDataGrid;
use SolidInvoice\DataGridBundle\Grid;
use SolidInvoice\DataGridBundle\GridBuilder\Action\Action;
use SolidInvoice\DataGridBundle\GridBuilder\Column\RelativeDateColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StatusColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\UserBundle\Entity\UserInvitation;

#[AsDataGrid(name: 'user_invitations', title: 'User Invitations')]
final class UserInvitationsGrid extends Grid
{
    public function entityFQCN(): string
    {
        return UserInvitation::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('email')
                ->label('Email Address'),
            RelativeDateColumn::new('created')
                ->label('Invited'),
            StatusColumn::new('status')
                ->label('Status')
                ->statusMap([
                    UserInvitation::STATUS_PENDING => 'warning',
                ]),
            StringColumn::new('invitedBy.email')
                ->label('Invited By'),
        ];
    }

    public function actions(): array
    {
        return [
            Action::new('_user_resend_invite', ['id' => 'id'])
                ->label('Resend Invitation')
                ->icon('mail'),
        ];
    }
}
