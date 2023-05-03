<?php

declare(strict_types=1);

use Symfony\Config\MakerConfig;

return static function (MakerConfig $config): void {
    $config->rootNamespace('SolidInvoice');
};
