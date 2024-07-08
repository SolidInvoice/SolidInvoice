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

use Symfony\Config\MonologConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (MonologConfig $config): void {
    $config
        ->handler('main')
        ->type('stream')
        ->path(sprintf('%s/%s.log', param('kernel.logs_dir'), param('kernel.environment')))
        ->level('debug')
        ->channels('!event');

    $config
        ->handler('console')
        ->type('console')
        ->processPsr3Messages(false)
        ->channels()
        ->elements(['!event', '!doctrine', '!console']);
};
