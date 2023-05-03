<?php

declare(strict_types=1);

use Symfony\Config\MonologConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

return static function (MonologConfig $config): void {

    $config
        ->handler('main')
        ->type('stream')
        ->path(sprintf('%s/%s.log', param('kernel.logs_dir'), param('kernel.environment')))
        ->level('debug')
        ->channels('!event');

    $config
        ->handler('console')
        ->type('console')
        ->processPsr3Messages(false)
        ->channels()
        ->elements(['!event', '!doctrine', '!console']);
};
