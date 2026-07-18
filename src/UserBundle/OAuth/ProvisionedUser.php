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

namespace SolidInvoice\UserBundle\OAuth;

use SolidInvoice\UserBundle\Entity\User;

/**
 * The outcome of {@see GoogleUserProvisioner::findOrCreate()}: the resolved user
 * and whether the record was created during provisioning.
 */
final readonly class ProvisionedUser
{
    public function __construct(
        public User $user,
        public bool $isNew,
    ) {
    }
}
