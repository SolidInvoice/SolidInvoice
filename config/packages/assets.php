<?php

declare(strict_types=1);

use Symfony\Config\FrameworkConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (FrameworkConfig $config): void {
    $config
        ->assets()
        ->jsonManifestPath(param('kernel.project_dir') . '/public/static/manifest.json');
};
