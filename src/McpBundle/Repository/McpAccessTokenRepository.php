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
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use LogicException;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
use SolidInvoice\McpBundle\Entity\OAuthAuthCode;
use SolidInvoice\McpBundle\Entity\OAuthClient;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;
use Symfony\Component\Uid\Ulid;

/**
 * @extends EntityRepository<McpAccessToken>
 */
final class McpAccessTokenRepository extends EntityRepository implements AccessTokenRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly OAuthAuthCodeRepository $authCodeRepository,
    ) {
        parent::__construct($registry, McpAccessToken::class);
    }

    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        ?string $userIdentifier = null,
    ): AccessTokenEntityInterface {
        if (! $clientEntity instanceof OAuthClient) {
            throw new \InvalidArgumentException('Expected OAuthClient instance.');
        }

        $token = new McpAccessToken();
        $token->setOAuthClient($clientEntity);

        $scopeValues = [];

        foreach ($scopes as $scope) {
            if ($scope instanceof ScopeEntityInterface) {
                $token->addScope($scope);
                $scopeValues[] = $scope->getIdentifier();
            }
        }

        $token->setScopeValues($scopeValues);

        if ($userIdentifier !== null && Ulid::isValid($userIdentifier)) {
            $user = $this->getEntityManager()
                ->getReference(\SolidInvoice\UserBundle\Entity\User::class, Ulid::fromString($userIdentifier));
            $token->setUser($user);
        }

        return $token;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        if (! $accessTokenEntity instanceof McpAccessToken) {
            throw new \InvalidArgumentException('Expected McpAccessToken instance.');
        }

        // Access tokens are minted either from an auth code (first issuance) or a refresh token.
        // Company binding is sourced from the previous auth code linked to this client+user.
        $this->bindCompanyIfMissing($accessTokenEntity);

        if ($this->findOneBy(['jti' => $accessTokenEntity->getIdentifier()]) !== null) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $this->save($accessTokenEntity);
    }

    public function revokeAccessToken(string $tokenId): void
    {
        $token = $this->findOneBy(['jti' => $tokenId]);

        if ($token instanceof McpAccessToken) {
            $token->revoke();
            $this->save($token);
        }
    }

    public function isAccessTokenRevoked(string $tokenId): bool
    {
        $token = $this->findOneBy(['jti' => $tokenId]);

        if (! $token instanceof McpAccessToken) {
            return true;
        }

        return $token->isRevoked();
    }

    public function findByJti(string $jti): ?McpAccessToken
    {
        return $this->findOneBy(['jti' => $jti]);
    }

    private function bindCompanyIfMissing(McpAccessToken $accessToken): void
    {
        try {
            // If company is already set (e.g. by a future direct binding), skip.
            $accessToken->getCompany();

            return;
        } catch (\Error) {
            // typed property not initialised — fall through to bind from prior auth code
        }

        $user = $accessToken->getUser();
        $client = $accessToken->getOAuthClient();

        $priorCode = $this->authCodeRepository->createQueryBuilder('c')
            ->andWhere('c.oauthClient = :client')
            ->andWhere('c.user = :user')
            ->andWhere('c.revoked = :revoked')
            ->setParameter('client', $client)
            ->setParameter('user', $user)
            ->setParameter('revoked', false)
            ->orderBy('c.created', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (! $priorCode instanceof OAuthAuthCode) {
            throw new LogicException('Cannot bind access token: no authorizing consent found for this client and user.');
        }

        $accessToken->setCompany($priorCode->getCompany());
    }
}
