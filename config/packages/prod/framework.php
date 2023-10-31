<?php

declare(strict_types=1);

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
