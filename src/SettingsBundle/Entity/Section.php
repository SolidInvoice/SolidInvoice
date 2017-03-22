<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="config_sections")
 * @ORM\Entity(repositoryClass="CSBill\SettingsBundle\Repository\SectionRepository")
 * @UniqueEntity("name")
 */
class Section
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="name", type="string", length=125, nullable=false, unique=true)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Section", inversedBy="children")
     */
    private $parent;

    /**
     * @var Collection|Section[]
     *
     * @ORM\OneToMany(targetEntity="Section", mappedBy="parent")
     */
    private $children;

    /**
     * @var Collection|Setting[]
     *
     * @ORM\OneToMany(targetEntity="Setting", mappedBy="section")
     */
    private $settings;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->settings = new ArrayCollection();
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
     * Set name.
     *
     * @param string $name
     *
     * @return Section
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get parent.
     *
     * @return Section
     */
    public function getParent(): ?Section
    {
        return $this->parent;
    }

    /**
     * Set parent.
     *
     * @param Section $parent
     *
     * @return Section
     */
    public function setParent(Section $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get children.
     *
     * @return Collection|Section[]
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Add child.
     *
     * @param Section $child
     *
     * @return Section
     */
    public function addChild(Section $child): self
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Remove child.
     *
     * @param Section $child
     *
     * @return Section
     */
    public function removeChild(Section $child): self
    {
        $this->children->removeElement($child);

        return $this;
    }

    /**
     * Get settings.
     *
     * @return Collection|Setting[]
     */
    public function getSettings(): Collection
    {
        return $this->settings;
    }

    /**
     * Add a setting.
     *
     * @param Setting $setting
     *
     * @return Section
     */
    public function addSetting(Setting $setting): self
    {
        $this->settings[] = $setting;
        $setting->setSection($this);

        return $this;
    }

    /**
     * Remove a setting.
     *
     * @param Setting $setting
     *
     * @return Section
     */
    public function removeSetting(Setting $setting): self
    {
        $this->settings->removeElement($setting);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): ?string
    {
        return $this->name;
    }
}
