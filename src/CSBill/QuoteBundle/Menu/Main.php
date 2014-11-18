<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Menu;

use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;
use Knp\Menu\ItemInterface;

/**
 * Menu ietms for quotes
 */
class Main extends AuthenticatedMenu
{
    /**
     * Menu builder for the quotes index
     *
     * @param $menu \Knp\Menu\ItemInterface
     */
    public function topMenu(ItemInterface $menu, array $parameters = array())
    {
        $menu->addChild('Quotes', array('route' => '_quotes_index'));
    }

    /**
     * Renders the quote index menu
     *
     * @param ItemInterface $menu
     */
    public function quotesMenu(ItemInterface $menu)
    {
        $menu->addChild('List Quotes', array('route' => '_quotes_index'));
        $menu->addChild('Create Quote', array('route' => '_quotes_create'));
    }
}
