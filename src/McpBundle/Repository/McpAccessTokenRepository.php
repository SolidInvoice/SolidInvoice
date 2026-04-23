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
use SolidInvoice\McpBundle\Entity\ConsentGrant;
use SolidInvoice\McpBundle\Entity\McpAccessToken;
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
        private readonly ConsentGrantRepository $consentGrantRepository,
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

        if ($userIdentifier !== null && $userIdentifier !== '') {
            try {
                $userUlid = Ulid::fromString($userIdentifier);
            } catch (\InvalidArgumentException) {
                $userUlid = null;
            }

            if ($userUlid !== null) {
                $user = $this->getEntityManager()
                    ->getReference(\SolidInvoice\UserBundle\Entity\User::class, $userUlid);
                $token->setUser($user);
            }
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

    /**
     * Record that the token was used now. Uses a direct UPDATE to avoid flushing
     * unrelated pending changes on the EM and to keep the happy-path cheap.
     */
    public function touch(McpAccessToken $token): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update(McpAccessToken::class, 't')
            ->set('t.lastUsedAt', ':now')
            ->where('t.jti = :jti')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('jti', $token->getJti())
            ->getQuery()
            ->execute();
    }

    private function bindCompanyIfMissing(McpAccessToken $accessToken): void
    {
        if ($accessToken->hasCompany()) {
            return;
        }

        $user = $accessToken->getUser();
        $client = $accessToken->getOAuthClient();

        // The consent grant is the stable record of this user's authorisation for
        // this client. Unlike the auth code it doesn't get revoked after the first
        // token exchange, so it also works for refresh-token flows later.
        $grant = $this->consentGrantRepository->findGrantForClientUser($client, $user);

        if (! $grant instanceof ConsentGrant) {
            throw new LogicException('Cannot bind access token: no consent grant found for this client and user.');
        }

        $accessToken->setCompany($grant->getCompany());
    }
}
