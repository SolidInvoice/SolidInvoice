<?php

declare(strict_types=1);

use Symfony\Config\NelmioAliceConfig;

return static function (NelmioAliceConfig $config): void {
    $config->functionsBlacklist([
        'current',
        'shuffle',
        'date',
        'time',
        'file',
        'md5',
        'sha1',
    ]);
};
