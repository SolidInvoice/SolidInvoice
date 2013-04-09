<?php

/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\ClientBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * CSBill\ClientBundle\Entity\Status
 *
 * @ORM\Table(name="client_status")
 * @ORM\Entity()
 * @UniqueEntity("name")
 */
class Status
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string $name
     *
     * @ORM\Column(name="name", type="string", length=125, nullable=false, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=125)
     */
    private $name;

    /**
     * @var ArrayCollection $clients
     *
     * @ORM\OneToMany(targetEntity="Client", mappedBy="status")
     * @Assert\Valid()
     */
    private $clients;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->clients = new ArrayCollection;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return Status
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add client
     *
     * @param  Client $client
     * @return Status
     */
    public function addClient(Client $client)
    {
        $this->clients[] = $client;
        $client->setStatus($this);

        return $this;
    }

    /**
     * Get clients
     *
     * @return ArrayCollection
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * Return the status as a string
     *
     * @return string
     */
    public function __toString()
    {
        return ucwords($this->getName());
    }
}
