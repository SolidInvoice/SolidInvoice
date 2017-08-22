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

namespace SolidInvoice\InvoiceBundle\Menu;

use SolidInvoice\ClientBundle\Menu\ClientMenu;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;

/**
 * Menu ietms for invoices.
 */
class Builder extends AuthenticatedMenu
{
    /**
     * Menu builder for the invoice index.
     *
     * @param ItemInterface $menu
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild(InvoiceMenu::main());
    }

    /**
     * Renders the invoice index menu.
     *
     * @param ItemInterface $menu
     * @param array         $options
     */
    public function invoicesMenu(ItemInterface $menu, array $options = [])
    {
        $menu->addChild(InvoiceMenu::listMenu());
        $menu->addChild(InvoiceMenu::create());

        if (isset($options['client'])) {
            $menu->addChild(ClientMenu::view($options['client']));
        }
    }

    /**
     * Renders the invoice edit menu.
     *
     * @param ItemInterface $menu
     * @param array         $options
     */
    public function invoicesEditMenu(ItemInterface $menu, array $options = [])
    {
        $this->invoicesMenu($menu);

        if (isset($options['invoice']) && $options['invoice'] instanceof Invoice) {
            $menu->addDivider();
            $menu->addChild(InvoiceMenu::view($options['invoice']));
        }
    }
}
