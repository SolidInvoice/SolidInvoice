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

use Symfony\Config\StofDoctrineExtensionsConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (StofDoctrineExtensionsConfig $config): void {
    $config->defaultLocale(env('SOLIDINVOICE_LOCALE'));

    $config->orm('default')
        ->timestampable(true);
};
