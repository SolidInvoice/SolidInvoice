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
    'framework' => [
        'mailer' => [
            'dsn' => env('SOLIDINVOICE_MAILER_DSN'),
            'envelope' => [
                'sender' => env('SOLIDINVOICE_MAILER_SENDER'),
            ],
        ],
    ],
]);
