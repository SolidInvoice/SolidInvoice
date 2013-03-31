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

/**
 * CSBill\ClientBundle\Entity\ContactType
 *
 * @ORM\Table(name="contact_types")
 * @ORM\Entity()
 */
class ContactType
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
     * @ORM\Column(name="name", type="string", length=45, unique=true, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=45)
     */
    private $name;

    /**
     * @var ArrayCollection $details
     *
     * @ORM\OneToMany(targetEntity="ContactDetail", mappedBy="type", cascade="ALL")
     */
    private $details;

    /**
     * Constructer
     */
    public function __construct()
    {
        $this->detail = new ArrayCollection;
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
     * @param  string      $name
     * @return ContactType
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
     * Add detail
     *
     * @param  ContactDetail $detail
     * @return ContactType
     */
    public function addDetail(ContactDetail $detail)
    {
        $this->details[] = $detail;
        $detail->setType($this);

        return $this;
    }

    /**
     * Get details
     *
     * @return ArrayCollection
     */
    public function getDetails()
    {
        return $this->detail;
    }

    /**
     * Return the contact type as a string
     */
    public function __toString()
    {
        return $this->getName();
    }
}
