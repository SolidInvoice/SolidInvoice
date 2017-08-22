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

namespace SolidInvoice\QuoteBundle\Menu;

use SolidInvoice\ClientBundle\Menu\ClientMenu;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;
use SolidInvoice\QuoteBundle\Entity\Quote;

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
     * @param array         $options
     */
    public function quotesMenu(ItemInterface $menu, array $options = [])
    {
        $menu->addChild(QuoteMenu::listMenu());
        $menu->addChild(QuoteMenu::create());
        if (isset($options['client'])) {
            $menu->addChild(ClientMenu::view($options['client']));
        }
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
