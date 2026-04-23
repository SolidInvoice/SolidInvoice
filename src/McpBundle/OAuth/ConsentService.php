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
use SolidInvoice\McpBundle\Entity\ConsentGrant;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\McpBundle\Repository\ConsentGrantRepository;
use SolidInvoice\UserBundle\Entity\User;

final class ConsentService
{
    public function __construct(
        private readonly ConsentGrantRepository $repository,
    ) {
    }

    /**
     * True only when the user previously ticked the "remember" checkbox for
     * this client + company + (at least) these scopes. Grants that exist
     * purely for token-binding (remember = false) do not auto-approve — the
     * user is prompted again so they can explicitly confirm each time.
     *
     * @param list<string> $requestedScopes
     */
    public function hasPriorConsent(OAuthClient $client, User $user, Company $company, array $requestedScopes): bool
    {
        $grant = $this->repository->findGrant($client, $user, $company);

        if (! $grant instanceof ConsentGrant || ! $grant->isRemember()) {
            return false;
        }

        foreach ($requestedScopes as $scope) {
            if (! \in_array($scope, $grant->getScopes(), true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Persist (or update) the consent grant. The grant is load-bearing for
     * token/refresh company binding, so it is always saved. The $remember
     * flag only controls whether {@see hasPriorConsent} returns true for
     * future authorise requests.
     *
     * @param list<string> $scopes
     */
    public function remember(OAuthClient $client, User $user, Company $company, array $scopes, bool $remember): void
    {
        $grant = $this->repository->findGrant($client, $user, $company);

        if (! $grant instanceof ConsentGrant) {
            $grant = new ConsentGrant();
            $grant->setClient($client)
                ->setUser($user)
                ->setCompany($company);
        }

        $merged = array_values(array_unique([...$grant->getScopes(), ...$scopes]));
        $grant->setScopes($merged);

        // Only flip "remember" on → never off. If a user ticked remember last
        // time, we respect that; a fresh grant without the checkbox just
        // leaves the default false.
        if ($remember) {
            $grant->setRemember(true);
        }

        $this->repository->save($grant);
    }

    public function revoke(OAuthClient $client, User $user, Company $company): void
    {
        $grant = $this->repository->findGrant($client, $user, $company);

        if ($grant instanceof ConsentGrant) {
            $this->repository->remove($grant);
        }
    }
}
