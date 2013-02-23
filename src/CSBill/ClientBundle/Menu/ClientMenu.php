<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Menu;

use Knp\Menu\ItemInterface;
use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;

class ClientMenu extends AuthenticatedMenu
{
    /**
     * Renders the top menu for clients
     *
     * @param ItemInterface $menu
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild('Clients', array('route' => '_clients_index'));
    }

    /**
     * Renders the client index menu
     *
     * @param ItemInterface $menu
     */
    public function clientsMenu(ItemInterface $menu)
    {
        $menu->addChild('List Clients', array('route' => '_clients_index'));
        $menu->addChild('Add Client', array('route' => '_clients_add'));
    }
}
