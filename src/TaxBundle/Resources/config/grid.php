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
        'tax_grid' => [
            'source' => [
                'repository' => 'SolidInvoiceTaxBundle:Tax',
                'method' => 'getGridQuery',
            ],
            'properties' => [
                'route' => '_tax_rates_edit',
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
                'rate' => [
                    'name' => 'rate',
                    'label' => 'Rate',
                    'editable' => false,
                    'cell' => 'percent',
                ],
                'type' => [
                    'name' => 'type',
                    'label' => 'Type',
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
                    'rate',
                    'name',
                    'type',
                ],
            ],
            'line_actions' => [
                'edit' => [
                    'icon' => 'edit',
                    'label' => 'tax.grid.actions.edit',
                    'route' => '_tax_rates_edit',
                    'route_params' => [
                        'id' => 'id',
                    ],
                ],
            ],
            'actions' => [
                'delete' => [
                    'label' => 'Delete',
                    'icon' => 'ban',
                    'confirm' => 'Are you sure you want to delete the selected rows?',
                    'action' => 'tax_grid_delete',
                    'className' => 'danger',
                ],
            ],
        ],
    ]);
};
