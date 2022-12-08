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

use Doctrine\ORM\Mapping as ORM;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;

/**
 * @ORM\Entity(repositoryClass="SolidInvoice\UserBundle\Repository\ApiTokenHistoryRepository")
 * @ORM\Table("api_token_history")
 */
class ApiTokenHistory
{
    use TimeStampable;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     *
     * @var int|null
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     *
     * @var string|null
     */
    private $ip;

    /**
     * @ORM\Column(type="string", length=125)
     *
     * @var string|null
     */
    private $resource;

    /**
     * @ORM\Column(type="string", length=25)
     *
     * @var string|null
     */
    private $method;

    /**
     * @ORM\Column(type="array")
     *
     * @var array
     */
    private $requestData;

    /**
     * @ORM\Column(type="string")
     *
     * @var string|null
     */
    private $userAgent;

    /**
     * @var ApiToken|null
     *
     * @ORM\ManyToOne(targetEntity="ApiToken", inversedBy="history")
     * @ORM\JoinColumn(name="token_id")
     */
    private $token;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getResource(): ?string
    {
        return $this->resource;
    }

    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return string
     */
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
     * @return array
     */
    public function getRequestData(): ?array
    {
        return $this->requestData;
    }

    public function setRequestData(array $requestData): self
    {
        $this->requestData = $requestData;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return ApiToken
     */
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
