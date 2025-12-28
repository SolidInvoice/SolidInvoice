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

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Icon;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

final class RecurringInvoiceMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_RECURRING_INVOICE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $recurringInvoices = $menu->addChild('invoice.menu.recurring.main', [
            'extras' => [
                'icon' => Icon::RECURRING_INVOICE,
            ],
        ]);

        $recurringInvoices->addChild(
            'invoice.menu.recurring.list',
            [
                'route' => '_invoices_index_recurring',
                'extras' => [
                    'icon' => Icon::RECURRING_INVOICE,
                ],
            ],
        );
        $recurringInvoices->addChild(
            'invoice.menu.recurring.create',
            [
                'extras' => [
                    'icon' => Icon::RECURRING_INVOICE_ADD,
                ],
                'route' => '_invoices_create_recurring',
            ],
        );
    }
}
