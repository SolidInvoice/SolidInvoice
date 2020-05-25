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

namespace SolidInvoice\QuoteBundle\Menu;

/**
 * Menu items for quotes.
 */
class QuoteMenu
{
    public static function list()
    {
        return [
            'quote.menu.list',
            [
                'route' => '_quotes_index',
                'extras' => [
                    'icon' => 'file-text-o',
                ],
            ],
        ];
    }

    public static function create()
    {
        return [
            'client.menu.create.quote',
            [
                'extras' => [
                    'icon' => 'file-text-o',
                ],
                'route' => '_quotes_create',
            ],
        ];
    }
}
