<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity(repositoryClass="CSBill\UserBundle\Repository\ApiTokenHistoryRepository")
 * @ORM\Table("api_token_history")
 */
class ApiTokenHistory
{
    use Entity\TimeStampable;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"js"})
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Serializer\Groups({"js"})
     *
     * @var string
     */
    private $ip;

    /**
     * @ORM\Column(type="string", length=125)
     * @Serializer\Groups({"js"})
     *
     * @var string
     */
    private $resource;

    /**
     * @ORM\Column(type="string", length=25)
     * @Serializer\Groups({"js"})
     *
     * @var string
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
     * @Serializer\Groups({"js"})
     *
     * @var string
     */
    private $userAgent;

    /**
     * @var ApiToken
     *
     * @ORM\ManyToOne(targetEntity="ApiToken", inversedBy="history")
     * @ORM\JoinColumn(name="token_id", referencedColumnName="id")
     */
    private $token;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * @param string $ip
     *
     * @return ApiTokenHistory
     */
    public function setIp(string $ip): ApiTokenHistory
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @return string
     */
    public function getResource(): string
    {
        return $this->resource;
    }

    /**
     * @param string $resource
     *
     * @return ApiTokenHistory
     */
    public function setResource(string $resource): ApiTokenHistory
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     *
     * @return ApiTokenHistory
     */
    public function setMethod(string $method): ApiTokenHistory
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * @param array $requestData
     *
     * @return ApiTokenHistory
     */
    public function setRequestData(array $requestData): ApiTokenHistory
    {
        $this->requestData = $requestData;

        return $this;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     *
     * @return ApiTokenHistory
     */
    public function setUserAgent(string $userAgent): ApiTokenHistory
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @return ApiToken
     */
    public function getToken(): ApiToken
    {
        return $this->token;
    }

    /**
     * @param ApiToken $token
     *
     * @return ApiTokenHistory
     */
    public function setToken(ApiToken $token): ApiTokenHistory
    {
        $this->token = $token;

        return $this;
    }
}
