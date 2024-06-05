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

use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('datagrid', [
        'users_list' => [
            'title' => 'Users',
            'icon' => 'user',
            'source' => [
                'repository' => User::class,
                'method' => 'getGridQuery',
            ],
            'columns' => [
                'email' => [
                    'name' => 'email',
                    'label' => 'email',
                    'editable' => false,
                    'cell' => 'email',
                ],
                'enabled' => [
                    'name' => 'enabled',
                    'label' => 'Enabled',
                    'editable' => false,
                    'cell' => 'boolean',
                ],
                'created' => [
                    'name' => 'created',
                    'label' => 'Created',
                    'editable' => false,
                    'cell' => 'date',
                ],
            ],
            'search' => [
                'fields' => [
                    'username',
                    'email',
                ],
            ],
            'actions' => [
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'user_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
        'users_invitations' => [
            'title' => 'Invitations',
            'icon' => 'envelope',
            'source' => [
                'repository' => UserInvitation::class,
                'method' => 'getGridQuery',
            ],
            'columns' => [
                'email' => [
                    'name' => 'email',
                    'label' => 'Email',
                    'editable' => false,
                    'cell' => 'email',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'created' => [
                    'name' => 'created',
                    'label' => 'Created',
                    'editable' => false,
                    'cell' => 'date',
                ],
            ],
            'search' => [
                'fields' => [
                    'email',
                ],
            ],
            'line_actions' => [
                're-send' => [
                    'icon' => 'envelope',
                    'label' => 'users.grid.invitation.actions.resend',
                    'route' => '_user_resend_invite',
                    'route_params' => [
                        'id' => 'id',
                    ],
                ],
            ],
            'actions' => [
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to remove the selected invited users?',
                    'action' => 'user_grid_delete_invitations',
                    'className' => 'danger',
                ],
            ],
        ],
    ]);
};
