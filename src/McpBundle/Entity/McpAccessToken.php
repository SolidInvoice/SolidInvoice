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
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\McpBundle\OAuth\ScopeEntity;
use SolidInvoice\McpBundle\Repository\McpAccessTokenRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: McpAccessTokenRepository::class)]
#[ORM\Table(name: McpAccessToken::TABLE_NAME)]
class McpAccessToken implements AccessTokenEntityInterface
{
    final public const string TABLE_NAME = 'mcp_access_token';

    use AccessTokenTrait;
    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    private string $jti;

    #[ORM\ManyToOne(targetEntity: OAuthClient::class)]
    #[ORM\JoinColumn(name: 'client_id', nullable: false, onDelete: 'CASCADE')]
    private OAuthClient $oauthClient;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'scope_values', type: Types::JSON)]
    private array $scopeValues = [];

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $revoked = false;

    #[ORM\Column(name: 'last_used_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    /**
     * @var array<string, ScopeEntityInterface>
     */
    private array $scopeEntities = [];

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        if (! isset($this->jti) || $this->jti === '') {
            throw new \LogicException('Access token identifier is not set.');
        }

        return $this->jti;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->jti = $identifier;
    }

    public function getJti(): string
    {
        if (! isset($this->jti) || $this->jti === '') {
            throw new \LogicException('Access token identifier is not set.');
        }

        return $this->jti;
    }

    public function getOAuthClient(): OAuthClient
    {
        return $this->oauthClient;
    }

    public function setOAuthClient(OAuthClient $client): self
    {
        $this->oauthClient = $client;

        return $this;
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->oauthClient;
    }

    public function setClient(ClientEntityInterface $client): void
    {
        if (! $client instanceof OAuthClient) {
            throw new \InvalidArgumentException('Expected OAuthClient instance.');
        }

        $this->oauthClient = $client;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUserIdentifier(): string|null
    {
        $id = $this->user->getId();

        return $id?->toRfc4122();
    }

    public function setUserIdentifier(string $identifier): void
    {
        // Identifier is derived from the persisted User relation; noop.
    }

    public function hasCompany(): bool
    {
        return isset($this->company);
    }

    /**
     * @return list<string>
     */
    public function getScopeValues(): array
    {
        return $this->scopeValues;
    }

    /**
     * @param list<string> $scopes
     */
    public function setScopeValues(array $scopes): self
    {
        $this->scopeValues = array_values($scopes);
        $this->scopeEntities = [];

        foreach ($this->scopeValues as $scope) {
            $this->addScope(new ScopeEntity($scope));
        }

        return $this;
    }

    public function addScope(ScopeEntityInterface $scope): void
    {
        $this->scopeEntities[$scope->getIdentifier()] = $scope;

        if (! \in_array($scope->getIdentifier(), $this->scopeValues, true)) {
            $this->scopeValues[] = $scope->getIdentifier();
        }
    }

    /**
     * @return list<ScopeEntityInterface>
     */
    public function getScopes(): array
    {
        if ($this->scopeEntities === [] && $this->scopeValues !== []) {
            foreach ($this->scopeValues as $scope) {
                $this->scopeEntities[$scope] = new ScopeEntity($scope);
            }
        }

        return array_values($this->scopeEntities);
    }

    public function getExpiresAt(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function getExpiryDateTime(): DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiryDateTime(DateTimeImmutable $dateTime): void
    {
        $this->expiresAt = $dateTime;
    }

    public function setExpiresAt(DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

        return $this;
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

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function touch(DateTimeImmutable $at): self
    {
        $this->lastUsedAt = $at;

        return $this;
    }

    public function setPrivateKey(CryptKeyInterface $privateKey): void
    {
        $this->privateKey = $privateKey;
    }
}
