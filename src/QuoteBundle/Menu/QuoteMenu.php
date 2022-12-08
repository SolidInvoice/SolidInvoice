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

namespace SolidInvoice\QuoteBundle\Menu;

use Knp\Menu\ItemInterface;

/**
 * Menu items for quotes.
 */
class QuoteMenu
{
    public static function list(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'quote.menu.list',
            [
                'route' => '_quotes_index',
                'extras' => [
                    'icon' => 'file-text-o',
                ],
            ],
        );
    }

    public static function create(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'client.menu.create.quote',
            [
                'extras' => [
                    'icon' => 'file-text-o',
                ],
                'route' => '_quotes_create',
            ],
        );
    }
}
