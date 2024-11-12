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

use Symfony\Config\WebpackEncoreConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (WebpackEncoreConfig $config): void {
    $config
        ->outputPath(param('kernel.project_dir') . '/public/static')
        ->strictMode(param('kernel.debug'))
        ->scriptAttributes('defer', true)
    ;
};
