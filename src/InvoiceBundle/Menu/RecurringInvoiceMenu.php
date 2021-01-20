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

namespace SolidInvoice\InvoiceBundle\Menu;

/**
 * Menu items for invoices.
 */
class RecurringInvoiceMenu
{
    public static function list(): array
    {
        return [
            'invoice.menu.recurring.list',
            [
                'route' => '_invoices_index_recurring',
                'extras' => [
                    'icon' => 'sync-alt',
                ],
            ],
        ];
    }

    public static function create(): array
    {
        return [
            'invoice.menu.recurring.create',
            [
                'extras' => [
                    'icon' => 'plus',
                ],
                'route' => '_invoices_create_recurring',
            ],
        ];
    }
}
