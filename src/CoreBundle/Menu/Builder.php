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

namespace SolidInvoice\CoreBundle\Menu;

use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class Builder extends AuthenticatedMenu
{
    /**
     * Build the user menu.
     *
     * @throws \InvalidArgumentException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function userMenu(ItemInterface $menu)
    {
        $menu->addDivider();
        $menu->addChild(MainMenu::api());
        $menu->addChild(MainMenu::logout());
    }

    /**
     * Build the system menu.
     *
     * @throws \InvalidArgumentException
     */
    public function systemMenu(ItemInterface $menu)
    {
        $menu->addChild(MainMenu::tax());
        $menu->addChild(MainMenu::users());
        $menu->addChild(MainMenu::settings());
    }
}
