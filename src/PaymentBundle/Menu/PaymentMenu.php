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

namespace SolidInvoice\PaymentBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\CoreBundle\Enum\Menu\MenuPriority;
use SolidWorx\Platform\PlatformBundle\Attributes\Menu\MenuBuilder;

final class PaymentMenu
{
    #[MenuBuilder(name: 'sidebar', priority: MenuPriority::PRIORITY_PAYMENT->value)]
    public function sidebar(ItemInterface $menu): void
    {
        $section = $menu->addChild(
            'payment.menu.main',
            [
                'extras' => [
                    'icon' => 'credit-card',
                ],
            ],
        );
        $section->addChild(
            'payment.menu.main',
            [
                'route' => '_payments_index',
                'extras' => [
                    'icon' => 'cash',
                ],
            ],
        );

        $section->addChild(
            'payment.menu.methods',
            [
                'route' => '_payment_settings_index',
                'extras' => [
                    'icon' => 'receipt',
                ],
            ],
        );
    }
}
