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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return App::config([
    'toggler' => [
        'config' => [
            'features' => [
                'allow_registration' => env('SOLIDINVOICE_ALLOW_REGISTRATION'),
                'google_oauth_login' => '@=env("SOLIDINVOICE_OAUTH_CLIENT_GOOGLE_CLIENT_ID") !== null && env("SOLIDINVOICE_OAUTH_CLIENT_GOOGLE_CLIENT_SECRET") !== null',
                'turnstile_captcha' => '@=env("SOLIDINVOICE_TURNSTILE_SITE_KEY") !== null && env("SOLIDINVOICE_TURNSTILE_SECRET_KEY") !== null',
                'saas_enabled' => '@=env("SOLIDINVOICE_MODE") === \'saas\'',
                'meilisearch_search' => '@=env("SOLIDINVOICE_MEILISEARCH_URL") !== "" && env("SOLIDINVOICE_MEILISEARCH_API_KEY") !== ""',
                'demo_enabled' => '@=env("SOLIDINVOICE_MODE") === \'demo\' && env("SOLIDINVOICE_DEMO_USERNAME") !== "" && env("SOLIDINVOICE_DEMO_PASSWORD") !== ""',
            ],
        ],
    ],
]);
