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

use SolidInvoice\UserBundle\Repository\ResetPasswordRequestRepository;

return App::config([
    'symfonycasts_reset_password' => [
        'request_password_repository' => ResetPasswordRequestRepository::class,
        'enable_garbage_collection' => true,
    ],
]);
