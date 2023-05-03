<?php

declare(strict_types=1);

use Symfony\Config\WebProfilerConfig;

return static function (WebProfilerConfig $con): void {
    $con->toolbar(true)
        ->interceptRedirects(false);
};
