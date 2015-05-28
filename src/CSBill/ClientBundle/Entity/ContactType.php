<?php

/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */
namespace CSBill\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="contact_types")
 * @ORM\Entity(repositoryClass="CSBill\ClientBundle\Repository\ContactTypeRepository")
 */
class ContactType
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45, unique=true, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=45)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     * @Assert\NotBlank()
     * @Assert\Length(max=45)
     */
    private $type = 'text';

    /**
     * @var array
     *
     * @ORM\Column(name="field_options", type="array", nullable=true)
     */
    private $options;

    /**
     * @var bool
     *
     * @ORM\Column(name="required", type="boolean", nullable=false)
     */
    private $required = false;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="ContactDetail", mappedBy="type")
     */
    private $details;

    /**
     * Constructer.
     */
    public function __construct()
    {
        $this->details = new ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ContactType
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the contact type required.
     *
     * @param bool $required
     *
     * @return ContactType
     */
    public function setRequired($required)
    {
        $this->required = (bool) $required;

        return $this;
    }

    /**
     * returns if the contact type is required.
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Add detail.
     *
     * @param ContactDetail $detail
     *
     * @return ContactType
     */
    public function addDetail(ContactDetail $detail)
    {
        $this->details[] = $detail;
        $detail->setType($this);

        return $this;
    }

    /**
     * Get details.
     *
     * @return ArrayCollection
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Return the contact type as a string.
     */
    public function __toString()
    {
        return $this->getName();
    }
}
