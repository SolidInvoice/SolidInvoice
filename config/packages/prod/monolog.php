<?php

declare(strict_types=1);

use Symfony\Config\MonologConfig;

return static function (MonologConfig $config): void {
    $config->handler('main')
        ->type('fingers_crossed')
        ->actionLevel('error')
        ->handler('nested')
        ->bufferSize(50)
        ->excludedHttpCode(404)
        ->excludedHttpCode(405);

    $config->handler('nested')
        ->type('stream')
        ->path('%kernel.logs_dir%/%kernel.environment%.log')
        ->level('debug');

    $config->handler('console')
        ->type('console')
        ->processPsr3Messages(false)
        ->channels()
        ->elements(['!event', '!doctrine']);
};
