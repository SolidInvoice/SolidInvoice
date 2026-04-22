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
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<OAuthClient>
 */
final class OAuthClientRepository extends EntityRepository implements ClientRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OAuthClient::class);
    }

    public function getClientEntity(string $clientIdentifier): ?ClientEntityInterface
    {
        if (! Ulid::isValid($clientIdentifier)) {
            return null;
        }

        return $this->findOneBy(['id' => Ulid::fromString($clientIdentifier)]);
    }

    public function validateClient(string $clientIdentifier, ?string $clientSecret, ?string $grantType): bool
    {
        $client = $this->getClientEntity($clientIdentifier);

        if (! $client instanceof OAuthClient) {
            return false;
        }

        if ($grantType !== null && ! \in_array($grantType, $client->getGrantTypes(), true)) {
            return false;
        }

        if ($client->getTokenEndpointAuthMethod() === 'none') {
            return $clientSecret === null || $clientSecret === '';
        }

        if ($clientSecret === null || $clientSecret === '') {
            return false;
        }

        $secretHash = $client->getSecretHash();

        return $secretHash !== null && password_verify($clientSecret, $secretHash);
    }

    public function findByUser(\SolidInvoice\UserBundle\Entity\User $user): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.createdBy = :user')
            ->setParameter('user', $user)
            ->orderBy('c.created', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
