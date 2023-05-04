<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config
        ->test(true)
        ->session()
        ->storageId('session.storage.mock_file');

    $config
        ->profiler()
        ->collect(false);
};
