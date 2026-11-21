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

use SolidInvoice\CoreBundle\MessengerMonitor\ProcessedMessage;

$env = static fn (string $name, string $default): string => $_ENV[$name] ?? $_SERVER[$name] ?? $default;

return App::config(
    $env('SOLIDINVOICE_MESSENGER_MONINTOR_HOST', '') !== '' ? [
            'zenstruck_messenger_monitor' => [
                'storage' => [
                    'store_input' => true,
                    'orm' => [
                        'entity_class' => ProcessedMessage::class,
                    ],
                ],
            ],
            // Mapped here, not auto-mapped from Entity/: its fields live on the bundle's mapped
            // superclass, which only exists when the bundle is registered (see Kernel::registerBundles()).
            'doctrine' => [
                'orm' => [
                    'entity_managers' => [
                        'default' => [
                            'mappings' => [
                                'SolidInvoiceMessengerMonitor' => [
                                    'is_bundle' => false,
                                    'type' => 'attribute',
                                    'dir' => param('kernel.project_dir') . '/src/CoreBundle/MessengerMonitor',
                                    'prefix' => 'SolidInvoice\\CoreBundle\\MessengerMonitor',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
    ] : []
);
