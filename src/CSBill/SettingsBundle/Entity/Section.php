<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
     * @ORM\OneToMany(targetEntity="Section", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\OneToMany(targetEntity="Setting", mappedBy="section")
     * @var ArrayCollection
     */
    private $settings;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->settings = new ArrayCollection();
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
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param  string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Section
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set parent
     *
     * @param  Section $parent
     * @return Section
     */
    public function setParent(Section $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get children
     *
     * @return ArrayCollection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add child
     *
     * @param  Section $child
     * @return Section
     */
    public function addChild(Section $child)
    {
        $this->children[] = $child;
        $child->setParent($this);

        return $this;
    }

    /**
     * Remove child
     *
     * @param  Section $child
     * @return Section
     */
    public function removeChild(Section $child)
    {
        $this->children->removeElement($child);

        return $this;
    }

    /**
     * Get settings
     *
     * @return ArrayCollection
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Add a setting
     * @param  Setting $setting
     * @return Section
     */
    public function addSetting(Setting $setting)
    {
        $this->settings[] = $setting;
        $setting->setSection($this);

        return $this;
    }

    /**
     * Remove a setting
     *
     * @param  Setting $setting
     * @return Section
     */
    public function removeSetting(Setting $setting)
    {
        $this->settings->removeElement($setting);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
