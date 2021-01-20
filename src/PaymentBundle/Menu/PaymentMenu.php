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

class PaymentMenu
{
    public static function main(): array
    {
        return [
            'payment.menu.main',
            [
                'route' => '_payments_index',
                'extras' => [
                    'icon' => 'credit-card',
                ],
            ],
        ];
    }

    public static function methods(): array
    {
        return [
            'payment.menu.methods',
            [
                'route' => '_payment_settings_index',
                'extras' => [
                    'icon' => 'credit-card',
                ],
            ],
        ];
    }
}
