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
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (FrameworkConfig $config): void {
    $httpClient = $config->httpClient();

    $httpClient->scopedClient('lemon_squeezy')
        ->baseUri('https://api.lemonsqueezy.com/v1/')
        ->authBearer(env('SOLIDINVOICE_LEMON_SQUEEZY_API_KEY'))
        ->header('Content-Type', 'application/vnd.api+json')
        ->header('Accept', 'application/vnd.api+json');

};
