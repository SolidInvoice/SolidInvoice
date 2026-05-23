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
use SolidInvoice\CoreBundle\Feature\UpgradePromptProvider;
use SolidInvoice\CoreBundle\Icon;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;

final readonly class QuoteMenu
{
    public function __construct(
        private FeatureGate $featureGate,
        private UpgradePromptProvider $upgradePromptProvider,
    ) {
    }

    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_QUOTE->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $extras = ['icon' => Icon::QUOTE];

        if (! $this->featureGate->isEnabled(Feature::Quotes->value)) {
            $planLabel = $this->upgradePromptProvider->menuLabel(Feature::Quotes->value);

            if ($planLabel !== null) {
                $extras['plan_label'] = $planLabel;
            }
        }

        $section = $menu->addChild(
            'quote.menu.main',
            [
                'extras' => $extras,
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
