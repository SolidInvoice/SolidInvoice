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

namespace SolidInvoice\ClientBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Icon;

class ClientMenu
{
    public static function list(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'client.menu.list',
            [
                'extras' => [
                    'icon' => Icon::CLIENT,
                ],
                'route' => '_clients_index',
            ],
        );
    }

    public static function add(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'client.menu.add',
            [
                'extras' => [
                    'icon' => 'user-plus',
                ],
                'route' => '_clients_add',
            ],
        );
    }
}
