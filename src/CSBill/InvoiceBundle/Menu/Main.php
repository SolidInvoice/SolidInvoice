<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Menu;

use CSBill\CoreBundle\Menu\Core\AuthenticatedMenu;
use CSBill\InvoiceBundle\Entity\Invoice;
use Knp\Menu\ItemInterface;

/**
 * Menu ietms for invoices
 */
class Main extends AuthenticatedMenu
{
    /**
     * Menu builder for the invoice index
     *
     * @param $menu \Knp\Menu\ItemInterface
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild('Invoices', array('route' => '_invoices_index'));
    }

    /**
     * Renders the invoice index menu
     *
     * @param ItemInterface $menu
     */
    public function invoicesMenu(ItemInterface $menu)
    {
        $menu->addChild(
            'List Invoices',
            array(
                'route' => '_invoices_index',
                'extras' => array(
                    'icon' => 'file-text-o',
                ),
            )
        );

        $menu->addChild(
            'Create Invoice',
            array(
                'route' => '_invoices_create',
                'extras' => array(
                    'icon' => 'file-text-o',
                ),
            )
        );
    }

    /**
     * Renders the invoice edit menu
     *
     * @param ItemInterface $menu
     * @param array         $options
     */
    public function invoicesEditMenu(ItemInterface $menu, array $options = array())
    {
        $this->invoicesMenu($menu);

        if (isset($options['invoice']) && $options['invoice'] instanceof Invoice) {
            $menu->addDivider();
            $menu->addChild(
                'View Invoice',
                array(
                    'extras' => array(
                        'icon' => 'eye',
                    ),
                    'route' => '_invoices_view',
                    'routeParameters' => array(
                        'id' => $options['invoice']->getId(),
                    ),
                )
            );
        }
    }
}
