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

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

// By default, password hashers are resource intensive and take time. This is
// important to generate secure password hashes. In tests however, secure hashes
// are not important, waste resources and increase test times. The following
// reduces the work factor to the lowest possible values.
return App::config([
    'security' => [
        'password_hashers' => [
            PasswordAuthenticatedUserInterface::class => [
                'algorithm' => 'auto',
                'cost' => 4, // Lowest possible value for bcrypt
                'time_cost' => 3, // Lowest possible value for argon
                'memory_cost' => 10, // Lowest possible value for argon
            ],
        ],
    ],
]);
