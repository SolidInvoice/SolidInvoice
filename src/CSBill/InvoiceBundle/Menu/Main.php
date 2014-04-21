<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Menu;

use Knp\Menu\ItemInterface;
use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;

/**
 * Menu ietms for invoices
 */
class Main extends AuthenticatedMenu
{
    /**
     * Menu builder for the invoice index
     *
     * @param $menu \Knp\Menu\ItemInterface
     */
    public function topMenu(ItemInterface $menu, array $parameters = array())
    {
        $menu->addChild('Invoices', array('route' => '_invoices_index'));
    }

    /**
     * Renders the invoice index menu
     *
     * @param ItemInterface $menu
     */
    public function invoicesMenu(ItemInterface $menu)
    {
        $menu->addChild('List Invoices', array('route' => '_invoices_index'));
        $menu->addChild('Create Invoice', array('route' => '_invoices_create'));
    }
}
