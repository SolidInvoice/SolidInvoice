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
use SolidInvoice\QuoteBundle\Menu\QuoteMenu;

/**
 * Menu items for invoices.
 */
class Builder extends AuthenticatedMenu
{
    /**
     * Menu builder for the invoice index.
     *
     * @throws \InvalidArgumentException
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild(InvoiceMenu::main());
    }

    /**
     * Renders the invoice index menu.
     *
     * @throws \InvalidArgumentException
     */
    public function invoicesMenu(ItemInterface $menu, array $options = [])
    {
        if (isset($options['client'])) {
            $menu->addHeader('Client Info');

            $menu->addChild(ClientMenu::view($options['client']));
        }

        // Quotes
        $menu->addHeader('quotes');
        $menu->addChild(QuoteMenu::listMenu());
        $menu->addChild(QuoteMenu::create($options['client'] ?? null));

        // Invoices
        $menu->addHeader('invoices');
        $menu->addChild(InvoiceMenu::listMenu());
        $menu->addChild(InvoiceMenu::create($options['client'] ?? null));
    }

    /**
     * Renders the invoice edit menu.
     *
     * @throws \InvalidArgumentException
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
