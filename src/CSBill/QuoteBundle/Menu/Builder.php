<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Menu;

use CSBill\MenuBundle\Core\AuthenticatedMenu;
use CSBill\MenuBundle\ItemInterface;
use CSBill\QuoteBundle\Entity\Quote;

/**
 * Menu items for quotes.
 */
class Builder extends AuthenticatedMenu
{
    /**
     * Menu builder for the quotes index.
     *
     * @param ItemInterface $menu
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild(QuoteMenu::main());
    }

    /**
     * Renders the quote index menu.
     *
     * @param ItemInterface $menu
     */
    public function quotesMenu(ItemInterface $menu)
    {
        $menu->addChild(QuoteMenu::listMenu());

        $menu->addChild(QuoteMenu::create());
    }

    /**
     * Renders the quote edit menu.
     *
     * @param ItemInterface $menu
     * @param array         $options
     */
    public function quotesEditMenu(ItemInterface $menu, array $options = [])
    {
        $this->quotesMenu($menu);

        if (isset($options['quote']) && $options['quote'] instanceof Quote) {
            $menu->addDivider();
            $menu->addChild(QuoteMenu::view($options['quote']));
        }
    }
}
