<?php

declare(strict_types=1);

use Symfony\Config\StofDoctrineExtensionsConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (StofDoctrineExtensionsConfig $config): void {
    $config->defaultLocale(env('locale'));
};
