<?php

declare(strict_types=1);

use Symfony\Config\WebpackEncoreConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (WebpackEncoreConfig $config): void {
    $config
        ->outputPath(param('kernel.project_dir') . '/public/static')
        ->strictMode(param('kernel.debug'));
};
