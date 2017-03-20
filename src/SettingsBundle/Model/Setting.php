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

namespace CSBill\SettingsBundle\Model;

use CSBill\SettingsBundle\Entity\Section;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass
 */
abstract class Setting
{
    /**
     * @ORM\Column(name="setting_key", type="string", length=125, nullable=false)
     */
    protected $key;

    /**
     * @ORM\Column(name="setting_value", type="text", nullable=true)
     */
    protected $value;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(name="field_type", type="string", nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(name="field_options", type="array", nullable=true)
     */
    protected $options;

    /**
     * @ORM\ManyToOne(targetEntity="Section", inversedBy="settings")
     */
    protected $section;

    /**
     * Get key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Set key.
     *
     * @param string $key
     *
     * @return Setting
     */
    public function setKey(string $key): Setting
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value.
     *
     * @param mixed $value
     *
     * @return Setting
     */
    public function setValue($value): Setting
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Setting
     */
    public function setDescription(string $description): Setting
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get section.
     *
     * @return Section
     */
    public function getSection(): Section
    {
        return $this->section;
    }

    /**
     * Set section.
     *
     * @param Section $section
     *
     * @return Setting
     */
    public function setSection(Section $section): Setting
    {
        $this->section = $section;

        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set type.
     *
     * @param string $type
     *
     * @return Setting
     */
    public function setType(string $type): Setting
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set type.
     *
     * @param array $options
     *
     * @return Setting
     */
    public function setOptions(array $options): Setting
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
