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

namespace SolidInvoice\TimeTrackingBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Icon;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Menu\Options;

final class TimeTrackingMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_TIME_TRACKING->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $section = $menu->addChild('time_tracking.menu.main', Options::create()->icon(Icon::TIME_TRACKING)->build());

        $section->addChild(
            'time_tracking.menu.list',
            Options::create()
                ->icon(Icon::TIME_TRACKING)
                ->route('_time_tracking_index')
                ->build(),
        );

        $section->addChild(
            'time_tracking.menu.log',
            Options::create()
                ->icon(Icon::TIME_ENTRY_ADD)
                ->route('_time_tracking_entry_create')
                ->build(),
        );
    }
}
