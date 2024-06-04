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

use SolidInvoice\QuoteBundle\Entity\Quote;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('datagrid', [
        'active_quote_grid' => [
            'title' => 'Active Quotes',
            'icon' => 'check',
            'source' => [
                'repository' => Quote::class,
                'method' => 'getGridQuery',
            ],
            'properties' => [
                'route' => '_quotes_view',
            ],
            'columns' => [
                'id' => [
                    'name' => 'quoteId',
                    'label' => 'ID',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'quote_status',
                ],
                'client' => [
                    'name' => 'client',
                    'label' => 'Client',
                    'editable' => false,
                    'cell' => 'client',
                ],
                'total' => [
                    'name' => 'total',
                    'label' => 'Total',
                    'editable' => false,
                    'cell' => 'money',
                    'formatter' => 'money',
                ],
                'tax' => [
                    'name' => 'tax',
                    'label' => 'Tax',
                    'editable' => false,
                    'cell' => 'money',
                    'formatter' => 'money',
                ],
                'discount' => [
                    'name' => 'discount',
                    'label' => 'Discount',
                    'editable' => false,
                    'cell' => 'string',
                    'formatter' => 'discount',
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
                    'status',
                    'total',
                    'c.name',
                ],
            ],
            'line_actions' => [
                'view' => [
                    'icon' => 'eye',
                    'label' => 'quote.grid.actions.view',
                    'route' => '_quotes_view',
                    'route_params' => [
                        'id' => 'id',
                    ],
                ],
                'edit' => [
                    'icon' => 'edit',
                    'label' => 'quote.grid.actions.edit',
                    'route' => '_quotes_edit',
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
                    'action' => 'quote_grid_archive',
                    'className' => 'warning',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'quote_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
        'archive_quote_grid' => [
            'title' => 'Archived Quotes',
            'icon' => 'archive',
            'source' => [
                'repository' => Quote::class,
                'method' => 'getArchivedGridQuery',
            ],
            'properties' => [
                'route' => '_quotes_view',
            ],
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'label' => 'ID',
                    'editable' => false,
                    'cell' => 'integer',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'quote_status',
                ],
                'client' => [
                    'name' => 'client',
                    'label' => 'Client',
                    'editable' => false,
                    'cell' => 'client',
                ],
                'total' => [
                    'name' => 'total',
                    'label' => 'Total',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'tax' => [
                    'name' => 'tax',
                    'label' => 'Tax',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'discount' => [
                    'name' => 'discount',
                    'label' => 'Discount',
                    'editable' => false,
                    'cell' => 'string',
                    'formatter' => 'discount',
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
                    'status',
                    'total',
                    'c.name',
                ],
            ],
            'actions' => [
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'quote_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
    ]);
};
