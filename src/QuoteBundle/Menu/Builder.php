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

namespace SolidInvoice\QuoteBundle\Menu;

use SolidInvoice\ClientBundle\Menu\ClientMenu;
use SolidInvoice\InvoiceBundle\Menu\InvoiceMenu;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;
use SolidInvoice\QuoteBundle\Entity\Quote;

/**
 * Menu items for quotes.
 */
class Builder extends AuthenticatedMenu
{
    /**
     * Menu builder for the quotes index.
     *
     * @param ItemInterface $menu
     *
     * @throws \InvalidArgumentException
     */
    public function topMenu(ItemInterface $menu)
    {
        $menu->addChild(QuoteMenu::main());
    }

    /**
     * Renders the quote index menu.
     *
     * @param ItemInterface $menu
     * @param array         $options
     *
     * @throws \InvalidArgumentException
     */
    public function quotesMenu(ItemInterface $menu, array $options = [])
    {
        if (isset($options['client'])) {
            $menu->addHeader('Client Info');
            $menu->addChild(ClientMenu::view($options['client']));
        }

        // Quotes
        $menu->addChild('quotes');
        $menu->addChild(QuoteMenu::listMenu());
        $menu->addChild(QuoteMenu::create($options['client'] ?? null));

        // Invoices
        $menu->addHeader('invoices');
        $menu->addChild(InvoiceMenu::listMenu());
        $menu->addChild(InvoiceMenu::create($options['client'] ?? null));
    }

    /**
     * Renders the quote edit menu.
     *
     * @param ItemInterface $menu
     * @param array         $options
     *
     * @throws \InvalidArgumentException
     */
    public function quotesEditMenu(ItemInterface $menu, array $options = [])
    {
        $this->quotesMenu($menu);

        if (isset($options['quote']) && $options['quote'] instanceof Quote) {
            $menu->addDivider();
            $menu->addChild(QuoteMenu::view($options['quote']));
        }
    }
}
