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

namespace SolidInvoice\McpBundle\Repository;

use Doctrine\Persistence\ManagerRegistry;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\McpBundle\Entity\ConsentGrant;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidInvoice\UserBundle\Entity\User;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<ConsentGrant>
 */
final class ConsentGrantRepository extends EntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConsentGrant::class);
    }

    public function findGrant(OAuthClient $client, User $user, Company $company): ?ConsentGrant
    {
        return $this->findOneBy([
            'client' => $client,
            'user' => $user,
            'company' => $company,
        ]);
    }

    /**
     * Find the most recent consent grant for a client+user pair, across any
     * company they might have. Used by the token-issuance flow to resolve the
     * bound company from the consent that authorised this client.
     */
    public function findGrantForClientUser(OAuthClient $client, User $user): ?ConsentGrant
    {
        return $this->findOneBy(
            ['client' => $client, 'user' => $user],
            ['created' => 'DESC'],
        );
    }
}
