<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\QuoteBundle\Menu;

use Knp\Menu\ItemInterface;
use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;

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
