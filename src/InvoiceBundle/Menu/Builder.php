<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InvoiceBundle\Menu;

use InvalidArgumentException;
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
    public function sidebar(ItemInterface $menu): void
    {
        $menu->addHeader('invoices');

        InvoiceMenu::list($menu);
        InvoiceMenu::create($menu);
        RecurringInvoiceMenu::list($menu);
        RecurringInvoiceMenu::create($menu);

        $menu->addDivider();
    }
}
