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

use SolidInvoice\ClientBundle\Entity\Client;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('datagrid', [
        'active_client_grid' => [
            'title' => 'Active Clients',
            'icon' => 'check',
            'source' => [
                'repository' => Client::class,
                'method' => 'getGridQuery',
            ],
            'properties' => [
                'route' => '_clients_view',
            ],
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'label' => 'ID',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'name' => [
                    'name' => 'name',
                    'label' => 'Name',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'website' => [
                    'name' => 'website',
                    'label' => 'Website',
                    'editable' => false,
                    'cell' => 'uri',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'client_status',
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
                    'name',
                    'website',
                    'status',
                ],
            ],
            'line_actions' => [
                'view' => [
                    'icon' => 'eye',
                    'label' => 'client.grid.actions.view',
                    'route' => '_clients_view',
                    'route_params' => [
                        'id' => 'id',
                    ],
                ],
                'edit' => [
                    'icon' => 'edit',
                    'label' => 'client.grid.actions.edit',
                    'route' => '_clients_edit',
                    'route_params' => [
                        'id' => 'id',
                    ],
                ],
            ],
            'actions' => [
                'archive' => [
                    'label' => 'Archive',
                    'icon' => 'archive',
                    'confirm' => 'Are you sure you want to archive the selected rows?',
                    'action' => 'client_grid_archive',
                    'className' => 'warning',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'client_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
        'archive_client_grid' => [
            'title' => 'Archived Clients',
            'icon' => 'archive',
            'source' => [
                'repository' => Client::class,
                'method' => 'getArchivedGridQuery',
            ],
            'properties' => [
                'route' => '_clients_view',
            ],
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'label' => 'ID',
                    'editable' => false,
                    'cell' => 'integer',
                ],
                'name' => [
                    'name' => 'name',
                    'label' => 'Name',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'website' => [
                    'name' => 'website',
                    'label' => 'Website',
                    'editable' => false,
                    'cell' => 'uri',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'client_status',
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
                    'name',
                    'website',
                    'status',
                ],
            ],
            'actions' => [
                'restore' => [
                    'label' => 'Restore',
                    'icon' => 'archive',
                    'confirm' => 'Are you sure you want to restore the selected rows?',
                    'action' => 'client_grid_restore',
                    'className' => 'info',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'client_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
    ]);
};
