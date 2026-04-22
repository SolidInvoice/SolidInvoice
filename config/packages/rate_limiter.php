<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config): void {
    $config->rateLimiter()->limiter('api_global')
        ->policy('sliding_window')
        ->limit(300)
        ->interval('1 minute');

    $config->rateLimiter()->limiter('mcp_oauth_register')
        ->policy('fixed_window')
        ->limit(60)
        ->interval('1 hour');
};
