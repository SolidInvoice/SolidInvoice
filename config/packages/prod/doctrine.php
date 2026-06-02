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
    'doctrine' => [
        'orm' => [
            'auto_generate_proxy_classes' => false,
            'proxy_dir' => param('kernel.build_dir') . '/doctrine/orm/Proxies',
            'entity_managers' => [
                'default' => [
                    'metadata_cache_driver' => [
                        'type' => 'pool',
                        'pool' => 'doctrine.system_cache_pool',
                    ],
                    'query_cache_driver' => [
                        'type' => 'pool',
                        'pool' => 'doctrine.system_cache_pool',
                    ],
                    'result_cache_driver' => [
                        'type' => 'pool',
                        'pool' => 'doctrine.result_cache_pool',
                    ],
                ],
            ],
        ],
    ],
]);
