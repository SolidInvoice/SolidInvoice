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

namespace SolidInvoice\McpBundle\OAuth;

use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;

/**
 * Holds the user/company choice from the consent step so the auth-code and
 * access-token repositories can bind the resulting tokens to the correct tenant.
 *
 * Scoped per request — set by the Authorize action immediately before calling
 * AuthorizationServer::completeAuthorizationRequest(), read by repositories
 * when persisting the auth code and access token.
 */
final class PendingAuthorization
{
    private ?User $user = null;

    private ?Company $company = null;

    public function set(User $user, Company $company): void
    {
        $this->user = $user;
        $this->company = $company;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function clear(): void
    {
        $this->user = null;
        $this->company = null;
    }
}
