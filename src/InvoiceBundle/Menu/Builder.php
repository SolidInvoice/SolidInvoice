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

use InvalidArgumentException;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\MenuBundle\Core\AuthenticatedMenu;
use SolidInvoice\MenuBundle\ItemInterface;

/**
 * Menu items for invoices.
 */
class Builder extends AuthenticatedMenu
{
    /**
     * Menu builder for the invoice index.
     *
     * @throws InvalidArgumentException
     */
    public function sidebar(ItemInterface $menu)
    {
        $menu->addHeader('invoices');
        $menu->addChild(InvoiceMenu::list());
        $menu->addChild(InvoiceMenu::create());

        $menu->addChild(RecurringInvoiceMenu::list());
        $menu->addChild(RecurringInvoiceMenu::create());

        $menu->addDivider();
    }
}
