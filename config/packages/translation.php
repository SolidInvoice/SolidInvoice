<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $config): void {
    $config->defaultLocale(env('locale'))
        ->translator()
        ->fallbacks(['en']);
};
