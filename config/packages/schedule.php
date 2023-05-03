<?php

declare(strict_types=1);

use Symfony\Config\ZenstruckScheduleConfig;

return static function (ZenstruckScheduleConfig $config): void {
    $config
        ->messenger()
        ->enabled(true)
        ->messageBus('messenger.default_bus');
};
