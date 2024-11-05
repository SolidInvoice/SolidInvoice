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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Table(name: ApiTokenHistory::TABLE_NAME)]
#[ORM\Entity(repositoryClass: ApiTokenHistoryRepository::class)]
class ApiTokenHistory
{
    final public const TABLE_NAME = 'api_token_history';

    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: UlidType::NAME)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $ip = null;

    #[ORM\Column(type: Types::STRING, length: 125)]
    private ?string $resource = null;

    #[ORM\Column(type: Types::STRING, length: 25)]
    private ?string $method = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'array')]
    private array $requestData = [];

    #[ORM\Column(type: Types::STRING)]
    private ?string $userAgent = null;

    #[ORM\ManyToOne(targetEntity: ApiToken::class, inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'token_id')]
    private ?ApiToken $token = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * @param array<string, mixed> $requestData
     */
    public function setRequestData(array $requestData): self
    {
        $this->requestData = $requestData;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getToken(): ?ApiToken
    {
        return $this->token;
    }

    public function setToken(ApiToken $token): self
    {
        $this->token = $token;

        return $this;
    }
}
