<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Menu;

use InvalidArgumentException;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class Builder extends AuthenticatedMenu
{
    /**
     * Build the user menu.
     *
     * @throws InvalidArgumentException
     * @throws ServiceCircularReferenceException
     * @throws ServiceNotFoundException
     */
    public function userMenu(ItemInterface $menu): void
    {
        $menu->addDivider();
        MainMenu::logout($menu);
    }

    /**
     * Build the system menu.
     *
     * @throws InvalidArgumentException
     */
    public function systemMenu(ItemInterface $menu): void
    {
        $menu->addDivider();
        $menu->addHeader('System');
        MainMenu::tax($menu);
        MainMenu::users($menu);
        MainMenu::integrations($menu);
        MainMenu::settings($menu);
        MainMenu::api($menu);
    }
}
