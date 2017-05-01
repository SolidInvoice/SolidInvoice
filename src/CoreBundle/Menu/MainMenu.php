<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Menu;

use CSBill\UserBundle\Entity\User;

class MainMenu
{
    /**
     * @param User $user
     *
     * @return array
     */
    public static function user(User $user): array
    {
        $username = $user->getUsername().' <b class="caret"></b>';

        return [
            'user',
            [
                'uri' => '#',
                'allow_safe_labels' => true,
                'label' => $username,
                'extras' => [
                    'safe_label' => true,
                    'icon' => 'user',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function profile(): array
    {
        return [
            'menu.top.profile', ['route' => 'fos_user_profile_show', 'extras' => ['icon' => 'user']],
        ];
    }

    /**
     * @return array
     */
    public static function api(): array
    {
        return [
            'menu.top.api', ['route' => '_api_keys_index', 'extras' => ['icon' => 'user-secret']],
        ];
    }

    /**
     * @return array
     */
    public static function logout(): array
    {
        return [
            'menu.top.logout',
            [
                'route' => 'fos_user_security_logout',
                'extras' => ['icon' => 'power-off'],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function system(): array
    {
        return [
            'menu.top.system',
            [
                'uri' => '#',
                'allow_safe_labels' => true,
                'extras' => [
                    'safe_label' => true,
                    'icon' => 'laptop',
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function settings(): array
    {
        return [
            'menu.top.settings',
            [
                'route' => '_settings',
                'extras' => ['icon' => 'cog'],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function tax(): array
    {
        return [
            'menu.top.tax',
            [
                'route' => '_tax_rates',
                'extras' => ['icon' => 'money'],
            ],
        ];
    }
}
