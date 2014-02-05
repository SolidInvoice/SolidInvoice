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
        $menu->addChild('List Clients', array('extras' => array('icon' => 'icon-file-alt'), 'route' => '_clients_index'));
        $menu->addChild('Add Client', array('extras' => array('icon' => 'icon-user'), 'route' => '_clients_add'));
    }

    /**
     * Renders the client view menu
     *
     * @param ItemInterface $menu
     */
    public function clientViewMenu(ItemInterface $menu)
    {
        $request = $this->container->get('request');

        $this->clientsMenu($menu);
        $menu->addChild('View Client', array('extras' => array('icon' => 'icon-eye-open'), 'route' => '_clients_view', 'routeParameters' => array('id' => $request->get('id'))));
        $menu->addChild('Create Invoice', array('extras' => array('icon' => 'icon-file-alt'), 'route' => '_invoices_create', 'routeParameters' => array('client' => $request->get('id'))));
        $menu->addChild('Create Quote', array('extras' => array('icon' => 'icon-file-alt'), 'route' => '_quotes_create', 'routeParameters' => array('client' => $request->get('id'))));
    }
}
