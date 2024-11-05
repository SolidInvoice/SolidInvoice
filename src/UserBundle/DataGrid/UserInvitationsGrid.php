<?php

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
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
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
            StringColumn::new('email'),
            DateTimeColumn::new('created'),
            StringColumn::new('status'),
            StringColumn::new('invitedBy'),
        ];
    }

    public function actions(): array
    {
        return [
            Action::new('_user_resend_invite', ['id' => 'id'])
                ->label('Resend Invitation')
                ->icon('envelope')
        ];
    }
}
