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

return static function (FrameworkConfig $config): void {
    $cache = $config
        ->cache()
        ->app('cache.adapter.filesystem')
        ->system('cache.adapter.system');

    $cache
        ->pool('doctrine.result_cache_pool')
        ->adapters(['cache.app']);

    $cache
        ->pool('doctrine.system_cache_pool')
        ->adapters(['cache.system']);
};
