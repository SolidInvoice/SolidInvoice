<?php

declare(strict_types = 1);

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
    /**
     * @return array
     */
    public static function main(): array
    {
        return [
            'client.menu.main',
            [
                'extras' => [
                    'icon' => Icon::CLIENT,
                ],
                'route' => '_clients_index',
            ],
        ];
    }

    /**
     * @return array
     */
    public static function listMenu(): array
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

    /**
     * @return array
     */
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

    /**
     * @param Client $client
     *
     * @return array
     */
    public static function view(Client $client): array
    {
        return [
            'client.menu.view',
            [
                'extras' => [
                    'icon' => 'eye',
                ],
                'route' => '_clients_view',
                'routeParameters' => [
                    'id' => $client->getId(),
                ],
            ],
        ];
    }
}
