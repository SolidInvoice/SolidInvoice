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

namespace SolidInvoice\PaymentBundle\Menu;

use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use Knp\Menu\ItemInterface;

class Builder extends AuthenticatedMenu
{
    /**
     * Renders the top menu for payments.
     *
     * @param ItemInterface $menu
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild(PaymentMenu::main());
    }

    /**
     * Renders the top menu for payments.
     *
     * @param ItemInterface $menu
     */
    public function topRightMenu(ItemInterface $menu)
    {
        $menu['menu.top.system']->addChild(PaymentMenu::methods());
    }
}
