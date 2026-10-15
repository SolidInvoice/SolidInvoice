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
use SolidInvoice\UserBundle\Affiliate\AffiliateProgram;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Menu\Options;
use SolidWorx\Platform\PlatformBundle\Menu\ProfileMenu as PlatformProfileMenu;

/**
 * "Refer & earn" as the last entry in the profile navigation, below every account page.
 */
final readonly class AffiliateMenu
{
    public function __construct(
        private AffiliateProgram $affiliateProgram,
    ) {
    }

    #[MenuBuilder(name: PlatformProfileMenu::NAME, priority: -100)]
    public function build(ItemInterface $menu): void
    {
        if (! $this->affiliateProgram->isEnabled()) {
            return;
        }

        // No `source` parameter: KnpMenu's route voter would then stop highlighting the entry.
        $menu->addChild('profile.nav.refer_and_earn', Options::create()->route('_profile_affiliate')->icon('gift')->build());
    }
}
