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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'framework' => [
        'rate_limiter' => [
            'limiters' => [
                'api_global' => [
                    'policy' => 'sliding_window',
                    'limit' => 300,
                    'interval' => '1 minute',
                ],
                'mcp_oauth_register' => [
                    'policy' => 'fixed_window',
                    'limit' => 60,
                    'interval' => '1 hour',
                ],
                'one_tap_nonce' => [
                    'policy' => 'sliding_window',
                    'limit' => 30,
                    'interval' => '1 minute',
                ],
                'one_tap_verify' => [
                    'policy' => 'sliding_window',
                    'limit' => 15,
                    'interval' => '1 minute',
                ],
            ],
        ],
    ],
]);
