<?php

declare(strict_types=1);

use Symfony\Config\WebProfilerConfig;

return static function (WebProfilerConfig $config): void {
    $config->toolbar(false)
        ->interceptRedirects(false);
};
