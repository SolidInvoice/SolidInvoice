<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Menu;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\InvoiceBundle\Entity\Invoice;

/**
 * Menu items for invoices.
 */
class InvoiceMenu
{
    public static function create(Client $client = null): array
    {
        return [
            'client.menu.create.invoice',
            [
                'extras' => [
                    'icon' => 'file-text-o',
                ],
                'route' => '_invoices_create',
                'routeParameters' => null !== $client ? ['client' => $client->getId()] : [],
            ],
        ];
    }

    public static function main(): array
    {
        return [
            'invoice.menu.main',
            [
                'route' => '_invoices_index',
                'extras' => [
                    'icon' => 'file-text-o',
                ],
            ],
        ];
    }

    public static function listMenu(): array
    {
        return [
            'invoice.menu.list',
            [
                'route' => '_invoices_index',
                'extras' => [
                    'icon' => 'file-text-o',
                ],
            ],
        ];
    }

    public static function view(Invoice $invoice): array
    {
        return [
            'invoice.menu.view',
            [
                'extras' => [
                    'icon' => 'eye',
                ],
                'route' => '_invoices_view',
                'routeParameters' => [
                    'id' => $invoice->getId(),
                ],
            ],
        ];
    }
}
