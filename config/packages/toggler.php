<?php

declare(strict_types=1);

use Symfony\Config\TogglerConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (TogglerConfig $config): void {
    $config->config()
        ->features('allow_registration', env('SOLIDINVOICE_ALLOW_REGISTRATION'));
};
