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

namespace SolidInvoice\CoreBundle\Menu;

use Knp\Menu\ItemInterface;
use SolidInvoice\UserBundle\Entity\User;

class MainMenu
{
    public static function user(ItemInterface $item, User $user): ItemInterface
    {
        $username = $user->getUserIdentifier() . ' <b class="caret"></b>';

        return $item->addChild(
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
        );
    }

    public static function profile(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.profile',
            ['route' => '_profile', 'extras' => ['icon' => 'user']],
        );
    }

    public static function api(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.api',
            ['route' => '_api_keys_index', 'extras' => ['icon' => 'user-secret']],
        );
    }

    public static function logout(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.logout',
            [
                'route' => '_logout',
                'extras' => ['icon' => 'power-off'],
            ],
        );
    }

    public static function system(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.system',
            [
                'uri' => '#',
                'allow_safe_labels' => true,
                'extras' => [
                    'safe_label' => true,
                    'icon' => 'laptop',
                ],
            ],
        );
    }

    public static function settings(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.settings',
            [
                'route' => '_settings',
                'extras' => ['icon' => 'cog'],
            ],
        );
    }

    public static function integrations(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.integrations',
            [
                'route' => '_notification_integration',
                'extras' => ['icon' => 'bell'],
            ],
        );
    }

    public static function tax(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.tax',
            [
                'route' => '_tax_rates',
                'extras' => ['icon' => 'money'],
            ],
        );
    }

    public static function users(ItemInterface $item): ItemInterface
    {
        return $item->addChild(
            'menu.top.users',
            [
                'route' => '_users_list',
                'extras' => ['icon' => 'users'],
            ],
        );
    }
}
