<?php

declare(strict_types=1);

use Symfony\Config\SensioFrameworkExtraConfig;

return static function (SensioFrameworkExtraConfig $config): void {
    $config
        ->router()
        ->annotations(false);
};
