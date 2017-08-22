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

use SolidInvoice\CoreBundle\Traits\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="SolidInvoice\UserBundle\Repository\UserRepository")
 * @Gedmo\Loggable()
 */
class User extends BaseUser
{
    use Entity\TimeStampable,
        Entity\SoftDeleteable;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", nullable=true)
     */
    protected $mobile;

    /**
     * @var Collection|ApiToken[]
     *
     * @ORM\OneToMany(targetEntity="ApiToken", mappedBy="user", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     */
    private $apiTokens;

    public function __construct()
    {
        parent::__construct();

        $this->apiTokens = new ArrayCollection();
    }

    /**
     * Don't return the salt, and rely on password_hash to generate a salt.
     */
    public function getSalt(): void
    {
        return;
    }

    /**
     * @return Collection|ApiToken[]
     */
    public function getApiTokens(): Collection
    {
        return $this->apiTokens;
    }

    /**
     * @param Collection|ApiToken[] $apiTokens
     *
     * @return User
     */
    public function setApiTokens(Collection $apiTokens): self
    {
        $this->apiTokens = $apiTokens;

        return $this;
    }

    /**
     * @return string
     */
    public function getMobile(): ?string
    {
        return $this->mobile;
    }

    /**
     * @param string $mobile
     *
     * @return User
     */
    public function setMobile(string $mobile): self
    {
        $this->mobile = $mobile;

        return $this;
    }
}
