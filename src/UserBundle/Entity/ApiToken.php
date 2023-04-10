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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidOrderedTimeGenerator;
use Ramsey\Uuid\UuidInterface;
use SolidInvoice\CoreBundle\Traits\Entity\CompanyAware;
use SolidInvoice\CoreBundle\Traits\Entity\TimeStampable;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="SolidInvoice\UserBundle\Repository\ApiTokenRepository")
 * @ORM\Table("api_tokens")
 * @UniqueEntity({"name", "user"})
 */
class ApiToken
{
    use TimeStampable;
    use CompanyAware;

    /**
     * @ORM\Column(type="uuid_binary_ordered_time")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UuidOrderedTimeGenerator::class)
     *
     * @var UuidInterface
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=125)
     * @Assert\NotBlank()
     *
     * @var string|null
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=125)
     *
     * @var string|null
     */
    private $token;

    /**
     * @var Collection<int, ApiTokenHistory>
     *
     * @ORM\OneToMany(targetEntity="ApiTokenHistory", mappedBy="token", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     */
    private $history;

    /**
     * @var UserInterface|null
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="apiTokens")
     * @ORM\JoinColumn(name="user_id")
     */
    private $user;

    public function __construct()
    {
        $this->history = new ArrayCollection();
    }

    public function getId(): UuidInterface
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
        $history->setToken($this);

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
}
