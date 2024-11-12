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

use Symfony\Config\DoctrineConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (DoctrineConfig $config): void {
    $config
        ->dbal()
        ->connection('default')
        ->dbnameSuffix('_test' . env('TEST_TOKEN')->default(''))
        ->driver(env('database_driver'))
        ->host(env('database_host'))
        ->port(env('database_port')->int())
        ->dbname(env('database_name'))
        ->user(env('database_user'))
        ->password(env('database_password'))
        ->serverVersion(env('database_version'));
};
