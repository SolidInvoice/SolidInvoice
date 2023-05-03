<?php

declare(strict_types=1);

use Symfony\Config\DebugConfig;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

// @phpstan-ignore-next-line
return static function (DebugConfig $config): void {
    // @phpstan-ignore-next-line
    $config->dumpDestination('tcp://' . env('VAR_DUMPER_SERVER'));
};
