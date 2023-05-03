<?php

declare(strict_types=1);

use Symfony\Config\DebugConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (DebugConfig $config): void {
    $config->dumpDestination('tcp://' . env('VAR_DUMPER_SERVER'));
};
