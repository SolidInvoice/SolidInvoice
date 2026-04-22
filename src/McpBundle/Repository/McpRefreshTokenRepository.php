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
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use SolidInvoice\McpBundle\Entity\McpRefreshToken;
use SolidWorx\Platform\PlatformBundle\Repository\EntityRepository;

/**
 * @extends EntityRepository<McpRefreshToken>
 */
final class McpRefreshTokenRepository extends EntityRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, McpRefreshToken::class);
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new McpRefreshToken();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        if (! $refreshTokenEntity instanceof McpRefreshToken) {
            throw new \InvalidArgumentException('Expected McpRefreshToken instance.');
        }

        if ($this->findOneBy(['identifier' => $refreshTokenEntity->getIdentifier()]) !== null) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $this->save($refreshTokenEntity);
    }

    public function revokeRefreshToken(string $tokenId): void
    {
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if ($token instanceof McpRefreshToken) {
            $token->revoke();
            $this->save($token);
        }
    }

    public function isRefreshTokenRevoked(string $tokenId): bool
    {
        $token = $this->findOneBy(['identifier' => $tokenId]);

        if (! $token instanceof McpRefreshToken) {
            return true;
        }

        return $token->isRevoked();
    }
}
