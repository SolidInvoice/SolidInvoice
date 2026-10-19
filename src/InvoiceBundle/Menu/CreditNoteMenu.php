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

final class CreditNoteMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_CREDIT_NOTE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $creditNotes = $menu->addChild('credit_note.menu.main', [
            'extras' => [
                'icon' => Icon::CREDIT_NOTE,
            ],
        ]);

        $creditNotes->addChild(
            'credit_note.menu.list',
            [
                'route' => '_credit_notes_index',
                'extras' => [
                    'icon' => Icon::CREDIT_NOTE,
                ],
            ],
        );

        $creditNotes->addChild(
            'credit_note.menu.create',
            [
                'route' => '_credit_notes_create',
                'extras' => [
                    'icon' => Icon::CREDIT_NOTE_ADD,
                ],
            ],
        );
    }
}
