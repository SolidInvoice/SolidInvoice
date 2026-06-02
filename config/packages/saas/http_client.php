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
        'http_client' => [
            'scoped_clients' => [
                'lemon_squeezy' => [
                    'base_uri' => 'https://api.lemonsqueezy.com/v1/',
                    'auth_bearer' => env('SOLIDINVOICE_LEMON_SQUEEZY_API_KEY'),
                    'headers' => [
                        'Content-Type' => 'application/vnd.api+json',
                        'Accept' => 'application/vnd.api+json',
                    ],
                ],
            ],
        ],
    ],
]);
