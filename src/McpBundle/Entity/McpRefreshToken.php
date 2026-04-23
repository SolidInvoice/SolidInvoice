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

namespace SolidInvoice\McpBundle\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\McpBundle\Repository\McpRefreshTokenRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: McpRefreshTokenRepository::class)]
#[ORM\Table(name: McpRefreshToken::TABLE_NAME)]
class McpRefreshToken implements RefreshTokenEntityInterface
{
    final public const string TABLE_NAME = 'mcp_refresh_token';

    use TimeStampable;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    /**
     * The token identifier (stored as the actual token value; opaque random string).
     */
    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    private string $identifier;

    #[ORM\ManyToOne(targetEntity: McpAccessToken::class)]
    #[ORM\JoinColumn(name: 'access_token_id', nullable: false, onDelete: 'CASCADE')]
    private McpAccessToken $accessToken;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $revoked = false;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getAccessToken(): AccessTokenEntityInterface
    {
        return $this->accessToken;
    }

    public function setAccessToken(AccessTokenEntityInterface $accessToken): void
    {
        if (! $accessToken instanceof McpAccessToken) {
            throw new \InvalidArgumentException('Expected McpAccessToken instance.');
        }

        $this->accessToken = $accessToken;
    }

    public function getMcpAccessToken(): McpAccessToken
    {
        return $this->accessToken;
    }

    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiryDateTime(DateTimeImmutable $dateTime): void
    {
        $this->expiresAt = $dateTime;
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): self
    {
        $this->revoked = true;

        return $this;
    }
}
