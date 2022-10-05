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

use Knp\Menu\ItemInterface;

/**
 * Menu items for invoices.
 */
class InvoiceMenu
{
    public static function list(ItemInterface $menu): ItemInterface
    {
        return $menu->addChild(
            'invoice.menu.list',
            [
                'route' => '_invoices_index',
                'extras' => [
                    'icon' => 'file-text-o',
                ],
            ],
        );
    }

    public static function create(ItemInterface $menu): ItemInterface
    {
        return $menu->addChild(
            'client.menu.create.invoice',
            [
                'route' => '_invoices_create',
                'extras' => [
                    'icon' => 'plus',
                ],
            ],
        );
    }
}
