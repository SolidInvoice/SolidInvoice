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

use SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;

#[ORM\Table('api_token_history')]
#[ORM\Entity(repositoryClass: ApiTokenHistoryRepository::class)]
class ApiTokenHistory
{
    use TimeStampable;
    use CompanyAware;

    #[ORM\Column(type: 'uuid_binary_ordered_time')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidOrderedTimeGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(type: 'string')]
    private ?string $ip = null;

    #[ORM\Column(type: 'string', length: 125)]
    private ?string $resource = null;

    #[ORM\Column(type: 'string', length: 25)]
    private ?string $method = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'array')]
    private array $requestData = [];

    #[ORM\Column(type: 'string')]
    private ?string $userAgent = null;

    #[ORM\ManyToOne(targetEntity: 'ApiToken', inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'token_id')]
    private ?ApiToken $token = null;

    public function getId(): ?UuidInterface
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
