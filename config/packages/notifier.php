<?php

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->notifier()
        ->enabled(true);
};
