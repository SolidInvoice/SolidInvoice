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

namespace SolidInvoice\ProductBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class ProductMenu
{
    public function __construct(
        private FeatureGate $featureGate,
        private UpgradePromptProvider $upgradePromptProvider,
    ) {
    }

    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_PRODUCT->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $extras = ['icon' => 'package'];

        if (! $this->featureGate->isEnabled(Feature::Products->value)) {
            $planLabel = $this->upgradePromptProvider->menuLabel(Feature::Products->value);

            if ($planLabel !== null) {
                $extras['plan_label'] = $planLabel;
            }
        }

        $menu->addChild(
            'menu.products',
            [
                'route' => '_products',
                'extras' => $extras,
            ],
        );
    }
}
