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
     */
    private ?UuidInterface $id = null;

    /**
     * @ORM\Column(type="string", length=125)
     * @Assert\NotBlank()
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", length=125)
     */
    private ?string $token = null;

    /**
     * @var Collection<int, ApiTokenHistory>
     *
     * @ORM\OneToMany(targetEntity="ApiTokenHistory", mappedBy="token", fetch="EXTRA_LAZY", cascade={"persist", "remove"}, orphanRemoval=true)
     * @ORM\OrderBy({"created" = "DESC"})
     */
    private Collection $history;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="apiTokens")
     * @ORM\JoinColumn(name="user_id")
     */
    private ?UserInterface $user = null;

    public function __construct()
    {
        $this->history = new ArrayCollection();
    }

    public function getId(): UuidInterface
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
}
