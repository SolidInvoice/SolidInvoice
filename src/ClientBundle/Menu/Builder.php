<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\ClientBundle\Menu;

use CSBill\InvoiceBundle\Menu\InvoiceMenu;
use CSBill\MenuBundle\Core\AuthenticatedMenu;
use CSBill\MenuBundle\ItemInterface;
use CSBill\QuoteBundle\Menu\QuoteMenu;

class Builder extends AuthenticatedMenu
{
    /**
     * Renders the top menu for clients.
     *
     * @param ItemInterface $menu
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild(ClientMenu::main());
    }

    /**
     * Renders the client index menu.
     *
     * @param ItemInterface $menu
     */
    public function clientsMenu(ItemInterface $menu)
    {
        $menu->addChild(ClientMenu::listMenu());
        $menu->addChild(ClientMenu::add());
    }

    /**
     * Renders the client view menu.
     *
     * @param ItemInterface $menu
     * @param array         $options
     */
    public function clientViewMenu(ItemInterface $menu, array $options = [])
    {
        $this->clientsMenu($menu);

        $menu->addDivider();

        $menu->addChild(ClientMenu::view($options['client'] ?? null));
        $menu->addChild(InvoiceMenu::create($options['client'] ?? null));
        $menu->addChild(QuoteMenu::create($options['client'] ?? null));
    }
}
