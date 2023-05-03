<?php

declare(strict_types=1);

use Symfony\Config\DamaDoctrineTestConfig;

return static function (DamaDoctrineTestConfig $config): void {
    $config->enableStaticConnection(true)
        ->enableStaticMetadataCache(true)
        ->enableStaticQueryCache(true);
};
