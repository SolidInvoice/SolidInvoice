<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $config): void {

    $config
        ->secret(env('secret'))
        ->phpErrors()
            ->log(true)
    ;

    $config->session()
        ->name('SOLIDINVOICE_APP');
};
