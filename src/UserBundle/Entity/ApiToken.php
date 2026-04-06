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

namespace SolidInvoice\UserBundle\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\ApiBundle\State\Processor\ApiTokenCreateProcessor;
use SolidInvoice\ApiBundle\State\Provider\ApiTokenCollectionProvider;
use SolidInvoice\ApiBundle\State\Provider\ApiTokenItemProvider;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\UserBundle\Repository\ApiTokenRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(ApiToken::TABLE_NAME)]
#[ORM\Entity(repositoryClass: ApiTokenRepository::class)]
#[UniqueEntity(['name', 'user'])]
#[ApiResource(
    uriTemplate: '/profile/api-tokens',
    operations: [
        new GetCollection(provider: ApiTokenCollectionProvider::class),
        new Post(
            processor: ApiTokenCreateProcessor::class,
            normalizationContext: ['groups' => ['api_token:read', 'api_token:create_read']],
        ),
    ],
    normalizationContext: ['groups' => ['api_token:read']],
    denormalizationContext: ['groups' => ['api_token:write']],
    graphQlOperations: [],
)]
#[ApiResource(
    uriTemplate: '/profile/api-tokens/{id}',
    operations: [
        new Get(provider: ApiTokenItemProvider::class),
        new Delete(provider: ApiTokenItemProvider::class),
    ],
    normalizationContext: ['groups' => ['api_token:read']],
    graphQlOperations: [],
)]
class ApiToken
{
    final public const TABLE_NAME = 'api_tokens';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    #[Groups(['api_token:read'])]
    private ?Ulid $id = null;

    #[ORM\Column(type: Types::STRING, length: 125)]
    #[Assert\NotBlank]
    #[Groups(['api_token:read', 'api_token:write'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, length: 125)]
    #[ApiProperty(writable: false, openapiContext: ['description' => 'The API token value. Only visible on creation.'])]
    #[Groups(['api_token:create_read'])]
    private ?string $token = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['api_token:read', 'api_token:write'])]
    private ?string $description = null;

    /**
     * @var Collection<int, ApiTokenHistory>
     */
    #[ORM\OneToMany(mappedBy: 'token', targetEntity: ApiTokenHistory::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    #[ORM\OrderBy(['created' => 'DESC'])]
    private Collection $history;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'apiTokens')]
    #[ORM\JoinColumn(name: 'user_id')]
    private ?UserInterface $user = null;

    public function __construct()
    {
        $this->history = new ArrayCollection();
    }

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Collection<int, ApiTokenHistory>
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function addHistory(ApiTokenHistory $history): self
    {
        $this->history[] = $history;
        $history->setToken($this)
            ->setCompany($this->getCompany());

        return $this;
    }

    public function removeHistory(ApiTokenHistory $history): self
    {
        $this->history->removeElement($history);

        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUsageCount(): int
    {
        return $this->history->count();
    }
}
