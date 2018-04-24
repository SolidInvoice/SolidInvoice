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

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidWorx\VuetifyBundle\Menu\Divider;
use SolidWorx\VuetifyBundle\Menu\Spacer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class Builder extends AuthenticatedMenu
{
    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(FactoryInterface $factory, TokenStorageInterface $tokenStorage)
    {
        $this->factory = $factory;
        $this->tokenStorage = $tokenStorage;
    }

    public function systemMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->addChild(new Spacer());

        $system = $menu->addChild(...MainMenu::system());
        $system->addChild(...MainMenu::settings());
        $system->addChild(...MainMenu::tax());

        $token = $this->tokenStorage->getToken();

        if ($token) {
            $user = $token->getUser();

            if ($user instanceof UserInterface) {
                $this->userMenu($menu, $user);
            }
        }

        return $menu;
    }

    private function userMenu(ItemInterface $menu, UserInterface $user)
    {
        $userMenu = $menu->addChild(...MainMenu::user($user));
        $userMenu->addChild(...MainMenu::profile());
        $userMenu->addChild(...MainMenu::api());
        $userMenu->addChild(new Divider());
        $userMenu->addChild(...MainMenu::logout());
    }
}
