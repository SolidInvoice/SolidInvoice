<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="SolidInvoice\UserBundle\Repository\ApiTokenRepository")
 * @ORM\Table("api_tokens")
 * @Gedmo\Loggable
 * @UniqueEntity({"name", "user"})
 */
class ApiToken
{
    use TimeStampable;

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=125)
     * @Assert\NotBlank()
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=125)
     *
     * @var string
     */
    private $token;

    /**
     * @var ApiTokenHistory[]|Collection<int, ApiTokenHistory>
     *
     * @ORM\OneToMany(targetEntity="ApiTokenHistory", mappedBy="token", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     */
    private $history;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="apiTokens")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    public function __construct()
    {
        $this->history = new ArrayCollection();
    }

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
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return ApiToken
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @return ApiToken
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return ApiTokenHistory[]|Collection<int, ApiTokenHistory>
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    /**
     * @return ApiToken
     *
     * @param ApiTokenHistory $history
     */
    public function addHistory(ApiTokenHistory $history): self
    {
        $this->history[] = $history;
        $history->setToken($this);

        return $this;
    }

    /**
     * @return ApiToken
     */
    public function removeHistory(ApiTokenHistory $history): self
    {
        $this->history->removeElement($history);

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @return ApiToken
     */
    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
