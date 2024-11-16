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

use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (FrameworkConfig $config): void {
    $config
        ->secret(env('SOLIDINVOICE_APP_SECRET'))
        ->phpErrors()
        ->log(true)
    ;

    $config->session()
        ->name('SOLIDINVOICE_APP');

    $config
        ->assets()
        ->jsonManifestPath(param('kernel.project_dir') . '/public/static/manifest.json')
    ;

    $config->secrets()
        ->enabled(true)
        ->vaultDirectory(env('SOLIDINVOICE_CONFIG_DIR'))
    ;
};
