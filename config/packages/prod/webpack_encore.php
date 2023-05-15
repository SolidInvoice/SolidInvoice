<?php

declare(strict_types=1);

use Symfony\Config\WebpackEncoreConfig;

return static function (WebpackEncoreConfig $config): void {
    $config->cache(true);
};
