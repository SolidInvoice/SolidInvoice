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

use Doctrine\Migrations\Version\Comparator;
use SolidInvoice\CoreBundle\Doctrine\Migrations\NaturalVersionComparator;

return App::config([
    'doctrine_migrations' => [
        'migrations_paths' => [
            'DoctrineMigrations' => param('kernel.project_dir') . '/migrations',
        ],
        'enable_profiler' => false,
        'storage' => [
            'table_storage' => [
                'table_name' => 'migration_versions',
            ],
        ],
        // Order migrations naturally so multi-digit parts (e.g. Version30000_10)
        // run after Version30000_9 rather than directly after Version30000_1.
        'services' => [
            Comparator::class => NaturalVersionComparator::class,
        ]
    ],
]);
