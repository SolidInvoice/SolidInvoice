<?php

declare(strict_types=1);

use Symfony\Config\DoctrineMigrationsConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (DoctrineMigrationsConfig $config): void {

    $config
        ->migrationsPath('DoctrineMigrations', param('kernel.project_dir') . '/migrations')
        ->storage()
            ->tableStorage()
                ->tableName('migration_versions')
    ;
};
