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
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\McpBundle\OAuth\ScopeEntity;
use SolidInvoice\McpBundle\Repository\OAuthAuthCodeRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: OAuthAuthCodeRepository::class)]
#[ORM\Table(name: OAuthAuthCode::TABLE_NAME)]
class OAuthAuthCode implements AuthCodeEntityInterface
{
    final public const string TABLE_NAME = 'mcp_oauth_auth_code';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(type: Types::STRING, length: 128, unique: true)]
    private string $identifier;

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

    #[ORM\Column(name: 'redirect_uri', type: Types::STRING, length: 2048, nullable: true)]
    private ?string $redirectUri = null;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $revoked = false;

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
        if (! isset($this->identifier) || $this->identifier === '') {
            throw new \LogicException('Auth code identifier not set.');
        }

        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
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
        // Derived from User relation; noop.
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
            $this->scopeEntities[$scope] = new ScopeEntity($scope);
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

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $uri): void
    {
        $this->redirectUri = $uri;
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

    public function isExpired(): bool
    {
        return $this->expiresAt < new DateTimeImmutable();
    }
}
