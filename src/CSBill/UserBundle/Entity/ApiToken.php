<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\UserBundle\Entity;

use CSBill\CoreBundle\Traits\Entity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="CSBill\UserBundle\Repository\ApiTokenRepository")
 * @ORM\Table("api_tokens")
 * @Gedmo\Loggable()
 * @UniqueEntity({"name", "user"})
 */
class ApiToken
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
     * @ORM\Column(type="string", length=125)
     * @Assert\NotBlank()
     * @Serializer\Groups({"js"})
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=125)
     * @Serializer\Groups({"js"})
     *
     * @var string
     */
    private $token;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ApiTokenHistory", mappedBy="token", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     * @ORM\OrderBy({"created" = "DESC"})
     * @Serializer\Groups({"js"})
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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return ApiToken
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return ApiToken
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getHistory()
    {
        return $this->history;
    }

    /**
     * @param ApiTokenHistory $history
     *
     * @return ApiToken
     */
    public function addHistory(ApiTokenHistory $history)
    {
        $this->history[] = $history;
        $history->setToken($this);

        return $this;
    }

    /**
     * @param ApiTokenHistory $history
     *
     * @return ApiToken
     */
    public function removeHistory(ApiTokenHistory $history)
    {
        $this->history->removeElement($history);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     *
     * @return ApiToken
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
