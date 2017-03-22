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

namespace CSBill\CoreBundle\Menu;

use CSBill\MenuBundle\Core\AuthenticatedMenu;
use CSBill\MenuBundle\ItemInterface;

class Builder extends AuthenticatedMenu
{
    /**
     * Build the user menu.
     *
     * @param ItemInterface $menu
     */
    public function userMenu(ItemInterface $menu)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $userMenu = $menu->addChild(MainMenu::user($user));

        $userMenu->setAttributes(['class' => 'dropdown']);
        $userMenu->setLinkAttributes(['class' => 'dropdown-toggle', 'data-toggle' => 'dropdown']);

        $userMenu->addChild(MainMenu::profile());
        $userMenu->addChild(MainMenu::api());
        $userMenu->addDivider();
        $userMenu->addChild(MainMenu::logout());

        $userMenu->setChildrenAttributes(['class' => 'dropdown-menu']);
    }

    /**
     * Build the system menu.
     *
     * @param ItemInterface $menu
     */
    public function systemMenu(ItemInterface $menu)
    {
        $system = $menu->addChild(MainMenu::system());

        $system->setAttributes(['class' => 'dropdown']);
        $system->setLinkAttributes(['class' => 'dropdown-toggle', 'data-toggle' => 'dropdown']);
        $system->addChild(MainMenu::settings());

        $system->addChild(MainMenu::tax());
        $system->setChildrenAttributes(['class' => 'dropdown-menu']);
    }
}
