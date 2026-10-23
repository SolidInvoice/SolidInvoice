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

namespace SolidInvoice\UserBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Menu\Options;
use SolidWorx\Platform\PlatformBundle\Menu\ProfileMenu as PlatformProfileMenu;

/**
 * The app's own pages in the profile navigation, under the platform's profile, password and
 * two-factor entries.
 */
final class ProfileMenu
{
    #[MenuBuilder(name: PlatformProfileMenu::NAME)]
    public function build(ItemInterface $menu): void
    {
        $menu->addChild('profile.nav.notifications', Options::create()->route('_profile_notifications')->icon('bell')->build());
        $menu->addChild('profile.nav.api_keys', Options::create()->route('_api_keys_index')->icon('key')->build());
        $menu->addChild('profile.nav.connected_apps', Options::create()->route('mcp_connected_apps_list')->icon('plug-connected')->build());
        $menu->addChild('profile.nav.data_export', Options::create()->route('_export_list')->icon('database-export')->build());
    }
}
