<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CS\QuoteBundle\Menu;

use Knp\Menu\ItemInterface;

/**
 * Menu ietms for quotes
 */
class Main
{
    /**
     * Menu builder for the quotes index
     *
     * @return \Knp\Menu\ItemInterface
     */
    public function quotesindexMenu(ItemInterface $menu, array $parameters)
    {
        $menu->addChild('List Quotes', array('route' => '_quote_index'));
        $menu->addChild('Add Quote', array('route' => '_quote_add'));

        return $menu;
    }
}
