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
     * @param list<string> $requestedScopes
     */
    public function hasPriorConsent(OAuthClient $client, User $user, Company $company, array $requestedScopes): bool
    {
        $grant = $this->repository->findGrant($client, $user, $company);

        if (! $grant instanceof ConsentGrant) {
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
     * @param list<string> $scopes
     */
    public function remember(OAuthClient $client, User $user, Company $company, array $scopes): void
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
