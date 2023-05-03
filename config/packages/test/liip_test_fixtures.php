<?php

declare(strict_types=1);

use Symfony\Config\LiipTestFixturesConfig;

return static function (LiipTestFixturesConfig $config): void {
    $config->keepDatabaseAndSchema(true);
};
