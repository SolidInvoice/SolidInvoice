<?php

declare(strict_types=1);

use Symfony\Config\HautelookAliceConfig;

return static function (HautelookAliceConfig $config): void {
    $config->fixturesPath(['fixtures']);
};
