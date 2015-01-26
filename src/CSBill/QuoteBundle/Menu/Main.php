<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\QuoteBundle\Menu;

use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;
use CSBill\QuoteBundle\Entity\Quote;
use Knp\Menu\ItemInterface;

/**
 * Menu ietms for quotes
 */
class Main extends AuthenticatedMenu
{
    /**
     * Menu builder for the quotes index
     *
     * @param $menu \Knp\Menu\ItemInterface
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild('Quotes', array('route' => '_quotes_index'));
    }

    /**
     * Renders the quote index menu
     *
     * @param ItemInterface $menu
     */
    public function quotesMenu(ItemInterface $menu)
    {
        $menu->addChild(
            'List Quotes',
            array(
                'route' => '_quotes_index',
                'extras' => array(
                    'icon' => 'file-text-o',
                ),
            )
        );

        $menu->addChild(
            'Create Quote',
            array(
                'route' => '_quotes_create',
                'extras' => array(
                    'icon' => 'file-text-o',
                ),
            )
        );
    }

    /**
     * Renders the quote edit menu
     *
     * @param ItemInterface $menu
     * @param array         $options
     */
    public function quotesEditMenu(ItemInterface $menu, array $options = array())
    {
        $this->quotesMenu($menu);

        if (isset($options['quote']) && $options['quote'] instanceof Quote) {
            $menu->addDivider();
            $menu->addChild(
                'View Quote',
                array(
                    'extras' => array(
                        'icon' => 'eye',
                    ),
                    'route' => '_quotes_view',
                    'routeParameters' => array(
                        'id' => $options['quote']->getId(),
                    ),
                )
            );
        }
    }
}
