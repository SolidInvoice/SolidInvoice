<?php

declare(strict_types=1);

use Symfony\Config\ZenstruckFoundryConfig;

return static function (ZenstruckFoundryConfig $config): void {
    $config->autoRefreshProxies(true);
};
