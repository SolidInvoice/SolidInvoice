<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu;

use Knp\Menu\ItemInterface as Item;
use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;

class Main extends AuthenticatedMenu
{
    /**
     * Build the user menu
     *
     * @param Item $menu
     */
    public function userMenu(Item $menu)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        $username = $user->getUsername() . ' <b class="caret"></b>';

        $userMenu = $menu->addChild(
            'user',
            array(
                'uri' => '#',
                'allow_safe_labels' => true,
                'label' => $username,
                'extras' => array(
                    'safe_label' => true,
                    'icon' => 'user'
                )
            )
        );

        $userMenu->setAttributes(array('class' => 'dropdown'));
        $userMenu->setLinkAttributes(array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'));

        $userMenu->addChild('Profile', array('route' => 'fos_user_profile_show', 'extras' => array('icon' => 'user')));
        $userMenu->addDivider();
        $userMenu->addChild(
            'Logout',
            array(
                'route' => 'fos_user_security_logout',
                'extras' => array('icon' => 'power-off')
            )
        );

        $userMenu->setChildrenAttributes(array('class' => 'dropdown-menu'));
    }

    /**
     * Build the system menu
     *
     * @param Item $menu
     */
    public function systemMenu(Item $menu)
    {
        $system = $menu->addChild(
            'system',
            array(
                'uri' => '#',
                'allow_safe_labels' => true,
                'label' => 'System <b class="caret"></b>',
                'extras' => array(
                    'safe_label' => true,
                    'icon' => 'laptop'
                )
            )
        );

        $system->setAttributes(array('class' => 'dropdown'));
        $system->setLinkAttributes(array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'));
        $system->addChild(
            'Settings',
            array(
                'route' => '_settings',
                'extras' => array('icon' => 'cog')
            )
        );

        $system->addChild(
            'Tax Rates',
            array(
                'route' => '_tax_rates',
                'extras' => array('icon' => 'money')
            )
        );
        $system->setChildrenAttributes(array('class' => 'dropdown-menu'));
    }
}
