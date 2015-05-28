<?php

/*
 * This file is part of CSBill package.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\PaymentBundle\Menu;

use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;
use Knp\Menu\ItemInterface;

class PaymentsMenu extends AuthenticatedMenu
{
    /**
     * Renders the top menu for payments.
     *
     * @param ItemInterface $menu
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild(
            'Payments',
            array(
                'route' => '_payments_index',
            )
        );
    }

    /**
     * Renders the top menu for payments.
     *
     * @param ItemInterface $menu
     */
    public function topRightMenu(ItemInterface $menu)
    {
        $menu['system']->addChild(
            'Payment Methods',
            array(
                'route' => '_payment_settings_index',
                'extras' => array('icon' => 'credit-card'),
            )
        );
    }
}
