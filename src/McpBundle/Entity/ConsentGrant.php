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
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\McpBundle\Repository\ConsentGrantRepository;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: ConsentGrantRepository::class)]
#[ORM\Table(name: ConsentGrant::TABLE_NAME)]
#[ORM\UniqueConstraint(name: 'uniq_consent_grant', columns: ['client_id', 'user_id', 'company_id'])]
class ConsentGrant
{
    final public const string TABLE_NAME = 'mcp_consent_grant';

    use CompanyAware;
    use TimeStampable;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\ManyToOne(targetEntity: OAuthClient::class)]
    #[ORM\JoinColumn(name: 'client_id', nullable: false, onDelete: 'CASCADE')]
    private OAuthClient $client;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $scopes = [];

    /**
     * When true, subsequent /oauth/authorize requests from this client for the
     * same user+company+scopes skip the consent UI (driven by the "remember"
     * checkbox on the consent page). When false, the grant still exists so the
     * token/refresh flow can resolve the bound company, but the consent page
     * is shown again on each authorise request.
     */
    #[ORM\Column(name: 'remember_consent', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $remember = false;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getClient(): OAuthClient
    {
        return $this->client;
    }

    public function setClient(OAuthClient $client): self
    {
        $this->client = $client;

        return $this;
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

    public function isRemember(): bool
    {
        return $this->remember;
    }

    public function setRemember(bool $remember): self
    {
        $this->remember = $remember;

        return $this;
    }
}
