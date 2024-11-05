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
use SolidInvoice\DataGridBundle\GridBuilder\Column\DateTimeColumn;
use SolidInvoice\DataGridBundle\GridBuilder\Column\StringColumn;
use SolidInvoice\UserBundle\Entity\User;

#[AsDataGrid(name: 'users_list', title: 'Users')]
final class UserGrid extends Grid
{
    public function entityFQCN(): string
    {
        return User::class;
    }

    public function columns(): array
    {
        return [
            StringColumn::new('email'),
            StringColumn::new('mobile'),
            DateTimeColumn::new('lastLogin'),
            StringColumn::new('enabled'),
        ];
    }

    /*public function actions(): array
    {
        return [
            Action::new('_user_resend_invite', ['id' => 'id'])
        ];
    }*/
}
