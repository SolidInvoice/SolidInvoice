<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config
        ->test(true)
        ->session()
        ->storageFactoryId('session.storage.factory.mock_file');

    $config
        ->profiler()
        ->collect(false);
};
