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

use Symfony\Config\DoctrineMigrationsConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (DoctrineMigrationsConfig $config): void {
    $config
        ->migrationsPath('DoctrineMigrations', param('kernel.project_dir') . '/migrations')
        ->enableProfiler(false)
        ->storage()
        ->tableStorage()
        ->tableName('migration_versions')
    ;
};
