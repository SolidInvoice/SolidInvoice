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

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $config): void {
    # By default, password hashers are resource intensive and take time. This is
    # important to generate secure password hashes. In tests however, secure hashes
    # are not important, waste resources and increase test times. The following
    # reduces the work factor to the lowest possible values.

    $config
        ->passwordHasher(PasswordAuthenticatedUserInterface::class)
        ->algorithm('auto')
        ->cost(4) # Lowest possible value for bcrypt
        ->timeCost(3) # Lowest possible value for argon
        ->memoryCost(10) # Lowest possible value for argon
    ;
};
