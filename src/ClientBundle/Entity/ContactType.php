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

namespace CSBill\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serialize;
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
     * @Serialize\Groups({"none"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=45, unique=true, nullable=false)
     * @Assert\NotBlank()
     * @Assert\Length(max=45)
     * @Serialize\Groups({"api", "js"})
     * @Serialize\SerializedName("type")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=45)
     * @Assert\NotBlank()
     * @Assert\Length(max=45)
     * @Serialize\Groups({"none"})
     */
    private $type = 'text';

    /**
     * @var array
     *
     * @ORM\Column(name="field_options", type="array", nullable=true)
     * @Serialize\Groups({"none"})
     */
    private $options;

    /**
     * @var bool
     *
     * @ORM\Column(name="required", type="boolean", nullable=false)
     * @Serialize\Groups({"none"})
     */
    private $required = false;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="AdditionalContactDetail", mappedBy="type")
     * @Serialize\Groups({"none"})
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
    public function getId(): int
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
    public function setName(string $name): ContactType
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
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
    public function setRequired(bool $required): ContactType
    {
        $this->required = (bool) $required;

        return $this;
    }

    /**
     * returns if the contact type is required.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Add detail.
     *
     * @param AdditionalContactDetail $detail
     *
     * @return ContactType
     */
    public function addDetail(AdditionalContactDetail $detail): ContactType
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
    public function getDetails(): ArrayCollection
    {
        return $this->details;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
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
