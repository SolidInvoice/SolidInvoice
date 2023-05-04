<?php

declare(strict_types=1);

use Symfony\Config\DoctrineConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (DoctrineConfig $config): void {
    $config
        ->dbal()
        ->connection('default')
        ->driver(env('database_driver'))
        ->host(env('database_host'))
        ->port(env('database_port')->int())
        ->dbname(env('database_name') . '_test')
        ->user(env('database_user'))
        ->password(env('database_password'))
        ->serverVersion(env('database_version'));
};
