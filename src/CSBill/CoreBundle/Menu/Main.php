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

use Knp\Menu\ItemInterface;
use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;

class Main extends AuthenticatedMenu
{
    /**
     * Menu builder for the quotes index
     *
     * @param ItemInterface $menu
     */
    public function topMenu(ItemInterface $menu)
    {
        $user = $this->container->get('security.context')->getToken()->getUser();

        $username = $user->getUsername() . ' <b class="caret"></b>';

        $menu->addChild(
            $username,
            array(
                'uri' => '#',
                'allow_safe_labels' => true,
                'extras' => array(
                    'safe_label' => true,
                    'icon' => 'user'
                )
            )
        );

        $topMenu = $menu[$username];

        $topMenu->setAttributes(array('class' => 'dropdown'));
        $topMenu->setLinkAttributes(array('class' => 'dropdown-toggle', 'data-toggle' => 'dropdown'));

        $topMenu->addChild('Profile', array('route' => 'fos_user_profile_show', 'extras' => array('icon' => 'user')));
        $topMenu->addChild('Settings', array('route' => '_settings', 'extras' => array('icon' => 'cog')));
        $topMenu->addDivider();
        $topMenu->addChild('Logout', array('route' => 'fos_user_security_logout', 'extras' => array('icon' => 'power-off')));

        $topMenu->setChildrenAttributes(array('class' => 'dropdown-menu'));
    }
}
