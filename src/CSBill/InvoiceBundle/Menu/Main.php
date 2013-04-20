<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
