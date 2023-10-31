<?php

declare(strict_types=1);

use Symfony\Config\TwigConfig;

return static function (TwigConfig $config): void {
    $config->strictVariables(true);
};
