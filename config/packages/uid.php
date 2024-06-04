<?php
declare(strict_types=1);

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->uid()
        ->defaultUuidVersion(7)
        ->timeBasedUuidVersion(7);
};
