<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
        $translator = $this->container->get('translator');

        $menu->addChild(
            $translator->trans('list_clients'),
            array(
                'extras' => array(
                    'icon' => 'file-o',
                ),
                'route' => '_clients_index',
            )
        );

        $menu->addChild(
            $translator->trans('add_client'),
            array(
                'extras' => array(
                    'icon' => 'user',
                ),
                'route' => '_clients_add',
            )
        );
    }

    /**
     * Renders the client view menu
     *
     * @param ItemInterface $menu
     */
    public function clientViewMenu(ItemInterface $menu)
    {
        $request = $this->container->get('request');
        $translator = $this->container->get('translator');

        $this->clientsMenu($menu);

        $menu->addChild(
            $translator->trans('view_client'),
            array(
                'extras' => array(
                    'icon' => 'eye',
                ),
                'route' => '_clients_view',
                'routeParameters' => array(
                    'id' => $request->get('id'),
                ),
            )
        );

        $menu->addChild(
            $translator->trans('create_invoice'),
            array(
                'extras' => array(
                    'icon' => 'file-o',
                ),
                'route' => '_invoices_create',
                'routeParameters' => array(
                    'client' => $request->get('id'),
                ),
            )
        );

        $menu->addChild(
            $translator->trans('create_quote'),
            array(
                'extras' => array(
                    'icon' => 'file-o',
                ),
                'route' => '_quotes_create',
                'routeParameters' => array(
                    'client' => $request->get('id'),
                ),
            )
        );
    }
}
