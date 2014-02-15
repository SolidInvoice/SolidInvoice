<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

        $topMenu->addChild('Settings', array('route' => '_settings', 'extras' => array('icon' => 'cog')));
        $topMenu->addDivider();
        $topMenu->addChild('Logout', array('uri' => '_logout', 'extras' => array('icon' => 'power-off')));

        $topMenu->setChildrenAttributes(array('class' => 'dropdown-menu'));
    }
}
