<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Menu;

use CSBill\InvoiceBundle\Entity\Invoice;
use Symfony\Component\HttpFoundation\Request;

/**
 * Menu items for invoices.
 */
class InvoiceMenu
{
    /**
     * @param Request|null $request
     *
     * @return array
     */
    public static function create(Request $request = null)
    {
        return [
            'client.menu.create.invoice',
            [
                'extras' => [
                    'icon' => 'file-text-o',
                ],
                'route' => '_invoices_create',
                'routeParameters' => null !== $request ? ['client' => $request->get('id')] : [],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function main()
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

    /**
     * @return array
     */
    public static function listMenu()
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

    /**
     * @param Invoice $invoice
     *
     * @return array
     */
    public static function view(Invoice $invoice)
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
