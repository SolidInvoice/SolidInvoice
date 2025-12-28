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

namespace SolidInvoice\QuoteBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Icon;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

final class QuoteMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_QUOTE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $section = $menu->addChild(
            'quote.menu.main',
            [
                'extras' => [
                    'icon' => Icon::QUOTE,
                ],
            ],
        );
        $section->addChild(
            'quote.menu.list',
            [
                'route' => '_quotes_index',
                'extras' => [
                    'icon' => Icon::QUOTE,
                ],
            ],
        );

        $section->addChild(
            'client.menu.create.quote',
            [
                'extras' => [
                    'icon' => Icon::QUOTE_ADD,
                ],
                'route' => '_quotes_create',
            ],
        );
    }
}
