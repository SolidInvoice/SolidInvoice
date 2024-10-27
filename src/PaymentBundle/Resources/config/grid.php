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

use SolidInvoice\PaymentBundle\Entity\Payment;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('datagrid', [
        'payment_grid' => [
            'source' => [
                'repository' => Payment::class,
                'method' => 'getGridQuery',
            ],
            'columns' => [
                'id' => [
                    'name' => 'id',
                    'label' => 'ID',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'invoice' => [
                    'name' => 'invoice',
                    'label' => 'Invoice',
                    'editable' => false,
                    'cell' => 'invoice',
                ],
                'client' => [
                    'name' => 'client',
                    'label' => 'Client',
                    'editable' => false,
                    'cell' => 'client',
                ],
                'total_amount' => [
                    'name' => 'totalAmount',
                    'label' => 'Total',
                    'editable' => false,
                    'cell' => 'money',
                    'formatter' => 'money',
                ],
                'currency_code' => [
                    'name' => 'currencyCode',
                    'label' => 'Currency',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'method' => [
                    'name' => 'method',
                    'label' => 'Method',
                    'editable' => false,
                    'cell' => 'string',
                    'formatter' => 'object',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'payment_status',
                ],
                'message' => [
                    'name' => 'message',
                    'label' => 'Message',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'reference' => [
                    'name' => 'reference',
                    'label' => 'Reference',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'notes' => [
                    'name' => 'notes',
                    'label' => 'Notes',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'completed' => [
                    'name' => 'completed',
                    'label' => 'Completed',
                    'editable' => false,
                    'cell' => 'date',
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
                    'totalAmount',
                    'currencyCode',
                    'm.name',
                    'status',
                    'message',
                    'c.name',
                ],
            ],
        ],
        'invoice_payment_grid' => [
            'source' => [
                'repository' => Payment::class,
                'method' => 'getGridQuery',
            ],
            'columns' => [
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'payment_status',
                ],
                'method' => [
                    'name' => 'method',
                    'label' => 'Method',
                    'editable' => false,
                    'cell' => 'string',
                    'formatter' => 'object',
                ],
                'completed' => [
                    'name' => 'completed',
                    'label' => 'Completed',
                    'editable' => false,
                    'cell' => 'date',
                ],
                'total_amount' => [
                    'name' => 'totalAmount',
                    'label' => 'Total',
                    'editable' => false,
                    'cell' => 'string',
                    'formatter' => 'money',
                ],
                'message' => [
                    'name' => 'message',
                    'label' => 'Message',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'reference' => [
                    'name' => 'reference',
                    'label' => 'Reference',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'notes' => [
                    'name' => 'notes',
                    'label' => 'Notes',
                    'editable' => false,
                    'cell' => 'string',
                ],
            ],
            'search' => [
                'fields' => [
                    'totalAmount',
                    'currencyCode',
                    'm.name',
                    'status',
                    'message',
                ],
            ],
        ],
        'client_payment_grid' => [
            'source' => [
                'repository' => Payment::class,
                'method' => 'getGridQuery',
            ],
            'columns' => [
                'invoice' => [
                    'name' => 'invoice',
                    'label' => 'Invoice',
                    'editable' => false,
                    'cell' => 'invoice',
                ],
                'status' => [
                    'name' => 'status',
                    'label' => 'Status',
                    'editable' => false,
                    'cell' => 'payment_status',
                ],
                'method' => [
                    'name' => 'method',
                    'label' => 'Method',
                    'editable' => false,
                    'cell' => 'string',
                    'formatter' => 'object',
                ],
                'completed' => [
                    'name' => 'completed',
                    'label' => 'Completed',
                    'editable' => false,
                    'cell' => 'date',
                ],
                'total_amount' => [
                    'name' => 'totalAmount',
                    'label' => 'Total',
                    'editable' => false,
                    'cell' => 'string',
                    'formatter' => 'money',
                ],
                'message' => [
                    'name' => 'message',
                    'label' => 'Message',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'reference' => [
                    'name' => 'reference',
                    'label' => 'Reference',
                    'editable' => false,
                    'cell' => 'string',
                ],
                'notes' => [
                    'name' => 'notes',
                    'label' => 'Notes',
                    'editable' => false,
                    'cell' => 'string',
                ],
            ],
            'search' => [
                'fields' => [
                    'totalAmount',
                    'currencyCode',
                    'status',
                    'message',
                ],
            ],
        ],
    ]);
};
