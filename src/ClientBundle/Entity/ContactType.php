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

namespace SolidInvoice\ClientBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serialize;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="contact_types")
 * @ORM\Entity(repositoryClass="SolidInvoice\ClientBundle\Repository\ContactTypeRepository")
 */
class ContactType
{
    /**
     * @var int|null
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue()
     * @Serialize\Groups({"client_api", "contact_api"})
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="name", type="string", length=45, unique=true)
     * @Assert\NotBlank()
     * @Assert\Length(max=45)
     * @Serialize\Groups({"client_api", "contact_api"})
     */
    private $name;

    /**
     * @var string|null
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
     * @var bool|null
     *
     * @ORM\Column(name="required", type="boolean")
     * @ORM\Column(name="required", type="boolean", nullable=false)
     * @Serialize\Groups({"none"})
     */
    private $required = false;

    /**
     * @var Collection<int, AdditionalContactDetail>
     *
     * @ORM\OneToMany(targetEntity="AdditionalContactDetail", mappedBy="type", orphanRemoval=true)
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
    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the contact type required.
     */
    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    /**
     * returns if the contact type is required.
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Add detail.
     */
    public function addDetail(AdditionalContactDetail $detail): self
    {
        $this->details[] = $detail;
        $detail->setType($this);

        return $this;
    }

    /**
     * Get details.
     *
     * @return Collection<int, AdditionalContactDetail>|null
     */
    public function getDetails(): ?Collection
    {
        return $this->details;
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Return the contact type as a string.
     */
    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
