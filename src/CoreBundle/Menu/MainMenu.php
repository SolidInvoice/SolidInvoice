<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Menu;

use SolidInvoice\UserBundle\Entity\User;

class MainMenu
{
    public static function user(User $user): array
    {
        return [
            'user',
            [
                'label' => $user->getUsername(),
                'extras' => [
                    'icon' => 'person',
                ],
            ],
        ];
    }

    public static function profile(): array
    {
        return [
            'menu.top.profile', ['route' => 'fos_user_profile_show', 'extras' => ['icon' => 'person']],
        ];
    }

    public static function api(): array
    {
        return [
            'menu.top.api', ['route' => '_api_keys_index', 'extras' => ['icon' => 'security']],
        ];
    }

    public static function logout(): array
    {
        return [
            'menu.top.logout',
            [
                'route' => 'fos_user_security_logout',
                'extras' => ['icon' => 'power_settings_new'],
            ],
        ];
    }

    public static function system(): array
    {
        return [
            'menu.top.system',
            [
                'allow_safe_labels' => true,
                'extras' => [
                    'safe_label' => true,
                    'icon' => 'laptop',
                ],
            ],
        ];
    }

    public static function settings(): array
    {
        return [
            'menu.top.settings',
            [
                'route' => '_settings',
                'extras' => ['icon' => 'settings'],
            ],
        ];
    }

    public static function tax(): array
    {
        return [
            'menu.top.tax',
            [
                'route' => '_tax_rates',
                'extras' => ['icon' => 'credit_card'],
            ],
        ];
    }
}
