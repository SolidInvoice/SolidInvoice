<?php

declare(strict_types=1);

use Symfony\Config\MonologConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (MonologConfig $config): void {

    $config->handler('main')
        ->type('fingers_crossed')
        ->actionLevel('error')
        ->handler('nested')
        ->excludedHttpCode(404)
        ->excludedHttpCode(405)
        ->channels('!event');

    $config->handler('nested')
        ->type('stream')
        ->path(sprintf('%s/%s.log', param('kernel.logs_dir'), param('kernel.environment')))
        ->level('debug');
};
