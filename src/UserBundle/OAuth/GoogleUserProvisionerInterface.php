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

interface GoogleUserProvisionerInterface
{
    /**
     * Resolve the internal user matching a verified Google identity, linking or
     * creating the record as needed. Returns null when no user can be resolved
     * (e.g. self-registration is disabled, or the identity would attach to an
     * account whose email address is not verified).
     */
    public function findOrCreate(GoogleIdentity $identity, ?User $currentUser = null): ?ProvisionedUser;
}
