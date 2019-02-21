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
     * @param ItemInterface $menu
     *
     * @throws \InvalidArgumentException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function userMenu(ItemInterface $menu)
    {
        /** @var User $user */
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
     *
     * @throws \InvalidArgumentException
     */
    public function systemMenu(ItemInterface $menu)
    {
        $system = $menu->addChild(MainMenu::system());

        $system->setAttributes(['class' => 'dropdown']);
        $system->setLinkAttributes(['class' => 'dropdown-toggle', 'data-toggle' => 'dropdown']);
        $system->addChild(MainMenu::settings());

        $system->addChild(MainMenu::tax());
        $system->addChild(MainMenu::users());
        $system->setChildrenAttributes(['class' => 'dropdown-menu']);
    }
}
