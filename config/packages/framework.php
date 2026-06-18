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
        'secret' => env('SOLIDINVOICE_APP_SECRET'),
        'php_errors' => [
            'log' => true,
        ],
        'trusted_headers' => [
            'x-forwarded-for',
            'x-forwarded-proto',
            'x-forwarded-port',
            'x-forwarded-host',
            'x-forwarded-prefix',
        ],
        'session' => [
            'name' => 'SOLIDINVOICE_APP',
        ],
        'secrets' => [
            'enabled' => true,
            'vault_directory' => env('SOLIDINVOICE_CONFIG_DIR'),
        ],
    ],
]);
