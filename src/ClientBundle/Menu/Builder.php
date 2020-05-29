<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ClientBundle\Menu;

use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;

class Builder extends AuthenticatedMenu
{
    /**
     * Renders the top menu for clients.
     *
     * @throws \InvalidArgumentException
     */
    public function sidebar(ItemInterface $menu)
    {
        $menu->addHeader('Clients');
        $menu->addChild(ClientMenu::list());
        $menu->addChild(ClientMenu::add());

        $menu->addDivider();
    }
}
