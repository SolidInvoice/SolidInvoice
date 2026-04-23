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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\McpBundle\Repository\OAuthClientRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: OAuthClientRepository::class)]
#[ORM\Table(name: OAuthClient::TABLE_NAME)]
class OAuthClient implements ClientEntityInterface
{
    final public const string TABLE_NAME = 'mcp_oauth_client';

    use TimeStampable;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $name;

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'redirect_uris', type: Types::JSON)]
    private array $redirectUris = [];

    /**
     * @var list<string>
     */
    #[ORM\Column(name: 'grant_types', type: Types::JSON)]
    private array $grantTypes = ['authorization_code', 'refresh_token'];

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $scopes = ['mcp:read'];

    #[ORM\Column(name: 'secret_hash', type: Types::STRING, length: 255, nullable: true)]
    private ?string $secretHash = null;

    #[ORM\Column(name: 'token_endpoint_auth_method', type: Types::STRING, length: 32)]
    private string $tokenEndpointAuthMethod = 'none';

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        $id = $this->id?->toRfc4122();

        if ($id === null || $id === '') {
            throw new \LogicException('Client identifier is not set.');
        }

        return $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getRedirectUris(): array
    {
        return $this->redirectUris;
    }

    /**
     * @param list<string> $redirectUris
     */
    public function setRedirectUris(array $redirectUris): self
    {
        $this->redirectUris = array_values($redirectUris);

        return $this;
    }

    public function getRedirectUri(): string|array
    {
        return $this->redirectUris;
    }

    /**
     * @return list<string>
     */
    public function getGrantTypes(): array
    {
        return $this->grantTypes;
    }

    /**
     * @param list<string> $grantTypes
     */
    public function setGrantTypes(array $grantTypes): self
    {
        $this->grantTypes = array_values($grantTypes);

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param list<string> $scopes
     */
    public function setScopes(array $scopes): self
    {
        $this->scopes = array_values($scopes);

        return $this;
    }

    public function getSecretHash(): ?string
    {
        return $this->secretHash;
    }

    public function setSecretHash(?string $secretHash): self
    {
        $this->secretHash = $secretHash;

        return $this;
    }

    public function getTokenEndpointAuthMethod(): string
    {
        return $this->tokenEndpointAuthMethod;
    }

    public function setTokenEndpointAuthMethod(string $tokenEndpointAuthMethod): self
    {
        $this->tokenEndpointAuthMethod = $tokenEndpointAuthMethod;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function isConfidential(): bool
    {
        return $this->tokenEndpointAuthMethod !== 'none';
    }
}
