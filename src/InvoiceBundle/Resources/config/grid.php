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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('datagrid', [
        'active_invoice_grid' => [
            'title' => 'Active Invoices',
            'icon' => 'check',
            'source' => [
                'repository' => 'SolidInvoiceInvoiceBundle:Invoice',
                'method' => 'getGridQuery',
            ],
            'properties' => [
                'route' => '_invoices_view',
            ],
            'columns' => [
                'id' => [
                    'name' => 'invoiceId',
                    'label' => 'ID',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'invoice_status',
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
                'invoiceDate' => [
                    'name' => 'invoiceDate',
                    'label' => 'Invoice Date',
                    'editable' => false,
                    'cell' => 'date',
                ],
                'due_date' => [
                    'name' => 'due',
                    'label' => 'Due Date',
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
                    'label' => 'invoice.grid.actions.view',
                    'route' => '_invoices_view',
                    'route_params' => [
                        'id' => 'id',
                    ],
                ],
                'edit' => [
                    'icon' => 'edit',
                    'label' => 'invoice.grid.actions.edit',
                    'route' => '_invoices_edit',
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
                    'action' => 'invoice_grid_archive',
                    'className' => 'warning',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'invoice_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
        'archive_invoice_grid' => [
            'title' => 'Archived Invoices',
            'icon' => 'archive',
            'source' => [
                'repository' => 'SolidInvoiceInvoiceBundle:Invoice',
                'method' => 'getArchivedGridQuery',
            ],
            'properties' => [
                'route' => '_invoices_view',
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
                    'cell' => 'invoice_status',
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
                    'action' => 'invoice_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
        'recurring_invoice_grid' => [
            'title' => 'Recurring Invoices',
            'icon' => 'sync-alt',
            'source' => [
                'repository' => 'SolidInvoiceInvoiceBundle:RecurringInvoice',
                'method' => 'getRecurringGridQuery',
            ],
            'properties' => [
                'route' => '_invoices_view_recurring',
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
                    'cell' => 'invoice_status',
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
                'dateStart' => [
                    'name' => 'dateStart',
                    'label' => 'Start Date',
                    'editable' => false,
                    'cell' => 'recurringInvoiceStart',
                ],
                'dateEnd' => [
                    'name' => 'dateEnd',
                    'label' => 'End Date',
                    'editable' => false,
                    'cell' => 'recurringInvoiceEnd',
                ],
                'frequency' => [
                    'name' => 'frequency',
                    'label' => 'Frequency',
                    'editable' => false,
                    'cell' => 'recurringInvoiceFrequency',
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
                    'label' => 'invoice.grid.actions.view',
                    'route' => '_invoices_view',
                    'route_params' => [
                        'id' => 'id',
                    ],
                ],
                'edit' => [
                    'icon' => 'edit',
                    'label' => 'invoice.grid.actions.edit',
                    'route' => '_invoices_edit_recurring',
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
                    'action' => 'invoice_grid_recurring_archive',
                    'className' => 'warning',
                ],
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'invoice_grid_recurring_delete',
                    'className' => 'danger',
                ],
            ],
        ],
        'archive_recurring_invoice_grid' => [
            'title' => 'Archived Recurring Invoices',
            'icon' => 'archive',
            'source' => [
                'repository' => 'SolidInvoiceInvoiceBundle:RecurringInvoice',
                'method' => 'getArchivedGridQuery',
            ],
            'properties' => [
                'route' => '_invoices_view_recurring',
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
                    'cell' => 'invoice_status',
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
                'dateStart' => [
                    'name' => 'dateStart',
                    'label' => 'Start Date',
                    'editable' => false,
                    'cell' => 'recurringInvoiceStart',
                ],
                'dateEnd' => [
                    'name' => 'dateEnd',
                    'label' => 'End Date',
                    'editable' => false,
                    'cell' => 'recurringInvoiceEnd',
                ],
                'frequency' => [
                    'name' => 'frequency',
                    'label' => 'Frequency',
                    'editable' => false,
                    'cell' => 'recurringInvoiceFrequency',
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
                    'action' => 'invoice_grid_recurring_delete',
                    'className' => 'danger',
                ],
            ],
        ],
    ]);
};
