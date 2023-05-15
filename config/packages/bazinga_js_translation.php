<?php

declare(strict_types=1);

use Symfony\Config\BazingaJsTranslationConfig;

return static function (BazingaJsTranslationConfig $config): void {
    $config->localeFallback('en');
};
