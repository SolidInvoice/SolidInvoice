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

// Dedicated pool for Google One Tap nonces. It MUST resolve to a shared backend
// (e.g. Redis) in multi-instance deployments — otherwise a nonce issued by one
// instance could be replayed against another. `cache.app` is the app's default
// pool; point it at a shared adapter in production.
return App::config([
    'framework' => [
        'cache' => [
            'pools' => [
                'cache.one_tap_nonce' => [
                    'adapter' => 'cache.app',
                    'default_lifetime' => 300,
                ],
            ],
        ],
    ],
]);
