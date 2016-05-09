<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Menu;

use CSBill\QuoteBundle\Entity\Quote;
use Symfony\Component\HttpFoundation\Request;

/**
 * Menu items for quotes.
 */
class QuoteMenu
{
    public static function main()
    {
        return [
            'quote.menu.main',
            [
                'route' => '_quotes_index',
                'extras' => [
                    'icon' => 'file-text-o',
                ],
            ],
        ];
    }

    public static function create(Request $request = null)
    {
        return [
            'client.menu.create.quote',
            [
                'extras' => [
                    'icon' => 'file-text-o',
                ],
                'route' => '_quotes_create',
                'routeParameters' => null !== $request ? ['client' => $request->get('id')] : [],
            ],
        ];
    }

    public static function listMenu()
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

    public static function view(Quote $quote)
    {
        return [
            'View Quote',
            [
                'extras' => [
                    'icon' => 'eye',
                ],
                'route' => '_quotes_view',
                'routeParameters' => [
                    'id' => $quote->getId(),
                ],
            ],
        ];
    }
}
