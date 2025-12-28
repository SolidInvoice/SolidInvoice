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

final class InvoiceMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_INVOICE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $invoices = $menu->addChild('invoice.menu.main', [
            'extras' => [
                'icon' => Icon::INVOICE,
            ],
        ]);

        $invoices->addChild(
            'invoice.menu.list',
            [
                'route' => '_invoices_index',
                'extras' => [
                    'icon' => Icon::INVOICE,
                ],
            ],
        );

        $invoices->addChild(
            'client.menu.create.invoice',
            [
                'route' => '_invoices_create',
                'extras' => [
                    'icon' => Icon::INVOICE_ADD,
                ],
            ],
        );
    }
}
