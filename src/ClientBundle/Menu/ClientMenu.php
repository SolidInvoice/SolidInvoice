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

namespace SolidInvoice\ClientBundle\Menu;

use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\CoreBundle\Icon;

class ClientMenu
{
    public static function list(): array
    {
        return [
            'client.menu.list',
            [
                'extras' => [
                    'icon' => Icon::CLIENT,
                ],
                'route' => '_clients_index',
            ],
        ];
    }

    public static function add(): array
    {
        return [
            'client.menu.add',
            [
                'extras' => [
                    'icon' => 'user-plus',
                ],
                'route' => '_clients_add',
            ],
        ];
    }
}
