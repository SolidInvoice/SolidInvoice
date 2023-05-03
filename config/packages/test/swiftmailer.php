<?php

declare(strict_types=1);

use Symfony\Config\SwiftmailerConfig;

return static function (SwiftmailerConfig $config): void {
    $config->mailer('default')
        ->disableDelivery(true);
};
